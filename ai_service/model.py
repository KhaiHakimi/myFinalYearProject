"""
FerryCast Route Cancellation Predictor
=======================================
Uses a Random Forest Classifier trained on synthetic maritime safety data
to predict whether a ferry departure will be cancelled based on real-time
weather and marine conditions.

The model considers 8 environmental features and outputs a cancellation
probability along with confidence metrics.  Training data is generated to
mirror realistic patterns observed in Southeast Asian ferry operations
(monsoon season spikes, calm straits, rough open sea, etc.).
"""

import os
# pyrefly: ignore [missing-import]
import numpy as np
import pandas as pd
# pyrefly: ignore [missing-import]
import joblib
from sklearn.ensemble import RandomForestClassifier
from sklearn.model_selection import train_test_split
from sklearn.metrics import accuracy_score, classification_report

MODEL_DIR = os.path.join(os.path.dirname(__file__), "trained_model")
MODEL_PATH = os.path.join(MODEL_DIR, "route_cancellation_rf.pkl")

# The exact feature order the model expects
FEATURE_NAMES = [
    "wind_speed",       # km/h
    "wave_height",      # metres
    "visibility",       # km
    "wind_direction",   # degrees (0-360)
    "wave_period",      # seconds between wave crests
    "swell_height",     # metres
    "hour_of_day",      # 0-23
    "month",            # 1-12
]


def generate_training_data(n_samples=12_000, seed=42):
    """
    Build a realistic synthetic dataset that encodes the physical
    relationships between weather features and cancellation outcomes.

    Rather than using purely random noise, the generator models several
    distinct maritime regimes:
      - Calm conditions  (low wind/waves → almost never cancelled)
      - Moderate weather (choppy but operable → occasionally cancelled)
      - Monsoon bursts   (high wind + swell → usually cancelled)
      - Fog / low vis    (independent trigger for cancellations)
      - Night departures (slightly higher tolerance thresholds)
    """
    rng = np.random.RandomState(seed)

    # Wind speed follows a skewed distribution (most days are calm)
    wind_speed = rng.gamma(2.5, 6.0, n_samples).clip(0, 100)

    # Wave height is correlated with wind, plus independent swell
    wave_base = wind_speed * 0.035 + rng.normal(0, 0.3, n_samples)
    wave_height = np.abs(wave_base + rng.exponential(0.3, n_samples)).clip(0, 8)

    # Visibility — mostly good, occasionally drops (fog / rain)
    visibility = rng.normal(12, 4, n_samples).clip(0.1, 30)
    fog_mask = rng.random(n_samples) < 0.08
    visibility[fog_mask] = rng.uniform(0.2, 2.5, fog_mask.sum())

    wind_direction = rng.uniform(0, 360, n_samples)
    wave_period = (6 + wave_height * 1.2 + rng.normal(0, 1, n_samples)).clip(2, 20)
    swell_height = (wave_height * 0.6 + rng.exponential(0.2, n_samples)).clip(0, 6)
    hour_of_day = rng.randint(5, 22, n_samples)   # ferries run ~05:00–21:00
    month = rng.randint(1, 13, n_samples)

    # --- cancellation logic (mirrors real operator decision rules) ----------
    #
    # Malaysian Maritime Enforcement Agency (MMEA) guidelines indicate that
    # ferry operations are typically suspended when:
    #   • Sustained winds exceed 40 km/h (Force 6+)
    #   • Significant wave height > 2.0 m
    #   • Visibility below 1 km
    #
    # We add probabilistic noise so the model learns soft boundaries rather
    # than hard cut-offs.

    cancel_score = np.zeros(n_samples)

    # Wind contribution (steepening sigmoid around 35 km/h)
    cancel_score += 1 / (1 + np.exp(-(wind_speed - 35) / 6)) * 40

    # Wave contribution (dominant factor; sigmoid centred at 1.8 m)
    cancel_score += 1 / (1 + np.exp(-(wave_height - 1.8) / 0.4)) * 45

    # Visibility contribution (drops below 3 km become dangerous)
    cancel_score += 1 / (1 + np.exp((visibility - 2.5) / 0.8)) * 25

    # Swell adds extra risk on top of wave height
    cancel_score += np.where(swell_height > 1.5, (swell_height - 1.5) * 8, 0)

    # Monsoon season (Nov-Feb) baseline uplift
    monsoon_months = np.isin(month, [11, 12, 1, 2])
    cancel_score[monsoon_months] += 8

    # Night departures — operators are more cautious
    night_mask = (hour_of_day < 7) | (hour_of_day > 19)
    cancel_score[night_mask] += 5

    # Convert to probability and sample binary labels
    cancel_prob = 1 / (1 + np.exp(-(cancel_score - 50) / 12))
    cancelled = (rng.random(n_samples) < cancel_prob).astype(int)

    df = pd.DataFrame({
        "wind_speed":     np.round(wind_speed, 1),
        "wave_height":    np.round(wave_height, 2),
        "visibility":     np.round(visibility, 1),
        "wind_direction": np.round(wind_direction, 0),
        "wave_period":    np.round(wave_period, 1),
        "swell_height":   np.round(swell_height, 2),
        "hour_of_day":    hour_of_day,
        "month":          month,
        "cancelled":      cancelled,
    })

    return df


def train_model(force_retrain=False):
    """
    Train (or load) the Random Forest model.

    The Random Forest was chosen specifically for this problem because:
      1. It handles non-linear decision boundaries between safe/cancel zones
      2. It naturally captures feature interactions (wind × wave combos)
      3. It provides probability calibration out of the box
      4. It's robust to the noise inherent in weather prediction data

    Hyperparameters are tuned for maritime safety prediction:
      - 200 estimators for stable probability estimates
      - max_depth=18 to capture complex weather patterns without overfitting
      - min_samples_leaf=8 to avoid memorising single outliers
      - class_weight='balanced' because cancellations are the minority class
    """
    if not force_retrain and os.path.exists(MODEL_PATH):
        return joblib.load(MODEL_PATH)

    import requests
    print("[FerryCast AI] Fetching real maritime training data from database...")
    try:
        # In production this will hit the hosted API. Locally it hits Laragon.
        api_url = os.environ.get("FERRYCAST_API_URL", "http://127.0.0.1/api")
        response = requests.get(f"{api_url}/ai/training-data", timeout=15)
        response.raise_for_status()
        data = response.json()
        
        if not data or len(data) < 50:
            print(f"[FerryCast AI] Warning: Only {len(data) if data else 0} real records found. Falling back to synthetic generator.")
            df = generate_training_data(n_samples=100_000)
        else:
            df = pd.DataFrame(data)
            # Impute missing features that the DB doesn't track yet but the model needs
            if "wind_direction" not in df.columns:
                df["wind_direction"] = np.random.uniform(0, 360, len(df))
            if "wave_period" not in df.columns:
                df["wave_period"] = (6 + df["wave_height"] * 1.2).clip(2, 20)
            if "swell_height" not in df.columns:
                df["swell_height"] = (df["wave_height"] * 0.6).clip(0, 6)
                
            print(f"[FerryCast AI] Successfully loaded {len(df)} historical records from the database.")
    except Exception as e:
        print(f"[FerryCast AI] API request failed: {e}. Falling back to synthetic generator.")
        df = generate_training_data(n_samples=100_000)

    X = df[FEATURE_NAMES]
    y = df["cancelled"]

    X_train, X_test, y_train, y_test = train_test_split(
        X, y, test_size=0.2, random_state=42, stratify=y
    )

    print("[FerryCast AI] Training Random Forest Classifier...")
    print(f"  -> {len(X_train):,} training samples, {len(X_test):,} test samples")
    print(f"  -> Cancellation rate: {y.mean():.1%}")

    clf = RandomForestClassifier(
        n_estimators=400,
        max_depth=25,
        min_samples_split=4,
        min_samples_leaf=2,
        max_features="sqrt",
        class_weight="balanced_subsample",
        random_state=42,
        n_jobs=-1,
    )

    clf.fit(X_train, y_train)

    y_pred = clf.predict(X_test)
    accuracy = accuracy_score(y_test, y_pred)
    print(f"\n[FerryCast AI] Model Accuracy: {accuracy:.2%}")
    print(classification_report(y_test, y_pred, target_names=["Operational", "Cancelled"]))

    # Feature importance ranking
    importances = clf.feature_importances_
    for name, imp in sorted(zip(FEATURE_NAMES, importances), key=lambda x: -x[1]):
        print(f"  {name:20s} -> {imp:.4f}")

    os.makedirs(MODEL_DIR, exist_ok=True)
    joblib.dump(clf, MODEL_PATH)
    print(f"\n[FerryCast AI] Model saved -> {MODEL_PATH}")

    return clf


def predict_cancellation(model, features: dict) -> dict:
    """
    Predict the cancellation probability for a single departure.

    Parameters
    ----------
    model : RandomForestClassifier
        The trained model.
    features : dict
        Must contain keys matching FEATURE_NAMES.

    Returns
    -------
    dict with:
        cancellation_probability  – float 0.0–1.0
        prediction                – 'Cancelled' | 'Operational'
        confidence                – float 0.0–1.0 (how sure the model is)
        risk_level                – 'Safe' | 'Caution' | 'High Risk'
        contributing_factors      – list of top reasons
        model_source              – always 'FerryCast AI'
    """
    row = np.array([[features.get(f, 0) for f in FEATURE_NAMES]])
    prob_array = model.predict_proba(row)
    prob = prob_array[0] if len(prob_array) > 0 else [0.0, 0.0]

    cancel_prob = float(prob[1]) if len(prob) > 1 else 0.0
    predicted_class = "Cancelled" if cancel_prob >= 0.50 else "Operational"
    confidence = float(max(prob))

    # Map probability to a tri-level risk label
    if cancel_prob >= 0.65:
        risk_level = "High Risk"
    elif cancel_prob >= 0.30:
        risk_level = "Caution"
    else:
        risk_level = "Safe"

    # Identify the dominant contributing factors for explainability
    factors = _explain_prediction(features, cancel_prob)

    return {
        "cancellation_probability": round(cancel_prob, 4),
        "prediction":               predicted_class,
        "confidence":               round(confidence, 4),
        "risk_level":               risk_level,
        "contributing_factors":     factors,
        "model_source":            "FerryCast AI",
    }


def _explain_prediction(features: dict, cancel_prob: float) -> list:
    """
    Generate human-readable explanations of why the model made its
    prediction, based on feature thresholds that maritime operators
    actually use when deciding to cancel a route.
    """
    factors = []

    wind = features.get("wind_speed", 0)
    wave = features.get("wave_height", 0)
    vis  = features.get("visibility", 30)
    swell = features.get("swell_height", 0)

    if wave >= 2.5:
        factors.append(f"Severe wave height ({wave:.1f}m) — exceeds safe operating limit")
    elif wave >= 1.5:
        factors.append(f"Elevated wave height ({wave:.1f}m) — approaching caution threshold")

    if wind >= 50:
        factors.append(f"Dangerous wind speed ({wind:.0f} km/h) — storm-force conditions")
    elif wind >= 30:
        factors.append(f"Strong winds ({wind:.0f} km/h) — may affect vessel stability")

    if vis < 1.5:
        factors.append(f"Near-zero visibility ({vis:.1f} km) — navigation hazard")
    elif vis < 4:
        factors.append(f"Reduced visibility ({vis:.1f} km) — fog or heavy rain likely")

    if swell >= 2.0:
        factors.append(f"Heavy ocean swell ({swell:.1f}m) — uncomfortable crossing expected")

    month = features.get("month", 6)
    if month in [11, 12, 1, 2]:
        factors.append("Northeast monsoon season — historically higher cancellation rates")

    hour = features.get("hour_of_day", 12)
    if hour < 7 or hour > 19:
        factors.append("Off-peak departure — reduced visibility and operator caution")

    if not factors:
        if cancel_prob < 0.15:
            factors.append("All conditions within safe operating parameters")
        else:
            factors.append("Marginal conditions — combined factors elevate risk slightly")

    return factors


# Allow direct execution for testing / re-training
if __name__ == "__main__":
    train_model(force_retrain=True)
