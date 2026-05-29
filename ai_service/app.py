"""
FerryCast AI Predictive Service
================================
Flask microservice that exposes the Random Forest route cancellation
model via a REST API.  The Laravel backend calls this service from
GeoIntelligenceService and TravelWindowController.

Endpoints:
    POST /predict          – Single departure prediction
    POST /predict/batch    – Batch predictions for multiple windows
    GET  /health           – Service health + model status
    GET  /model/stats      – Feature importances and training metrics
"""

import os
import sys
import time
import logging
from datetime import datetime

# pyrefly: ignore [missing-import]
from flask import Flask, request, jsonify
from flask_cors import CORS

# Ensure the package root is importable
sys.path.insert(0, os.path.dirname(__file__))
from model import train_model, predict_cancellation, FEATURE_NAMES
from recommendation import recommend as recommend_journey

# ---------------------------------------------------------------------------
# App setup
# ---------------------------------------------------------------------------

app = Flask(__name__)
CORS(app)

logging.basicConfig(
    level=logging.INFO,
    format="%(asctime)s [%(levelname)s] %(message)s",
)
log = logging.getLogger("ferrycast_ai")

# Train or load the model once at startup
log.info("Initialising FerryCast AI engine...")
start = time.time()
model = train_model(force_retrain=False)
elapsed = time.time() - start
log.info(f"Model ready in {elapsed:.2f}s")


# ---------------------------------------------------------------------------
# Routes
# ---------------------------------------------------------------------------

@app.route("/health", methods=["GET"])
def health():
    """Quick liveness check — Laravel pings this before sending predictions."""
    return jsonify({
        "status":      "online",
        "service":     "FerryCast AI Predictive Engine",
        "model":       "RandomForest (200 trees, depth 18)",
        "features":    FEATURE_NAMES,
        "timestamp":   datetime.utcnow().isoformat() + "Z",
    })


@app.route("/predict", methods=["POST"])
def predict():
    """
    Predict cancellation probability for a single departure.

    Expected JSON body:
    {
        "wind_speed":     35.2,
        "wave_height":    1.8,
        "visibility":     6.0,
        "wind_direction": 210,
        "wave_period":    8.5,
        "swell_height":   1.2,
        "hour_of_day":    14,
        "month":          12
    }
    """
    data = request.get_json(silent=True)
    if not data:
        return jsonify({"error": "JSON body required"}), 400

    features = _extract_features(data)
    result = predict_cancellation(model, features)

    log.info(
        f"Prediction: {result['prediction']} "
        f"(cancel={result['cancellation_probability']:.1%}, "
        f"confidence={result['confidence']:.1%})"
    )

    return jsonify(result)


@app.route("/predict/batch", methods=["POST"])
def predict_batch():
    """
    Predict cancellation probability for multiple departures at once.

    The TravelWindowController sends all schedule windows in a single
    request to minimise round-trip latency.

    Expected JSON body:
    {
        "windows": [
            { "schedule_id": 42, "wind_speed": 12, "wave_height": 0.5, ... },
            { "schedule_id": 43, "wind_speed": 38, "wave_height": 2.1, ... }
        ]
    }
    """
    data = request.get_json(silent=True)
    if not data or "windows" not in data:
        return jsonify({"error": "'windows' array required"}), 400

    windows = data["windows"]
    if not isinstance(windows, list):
        return jsonify({"error": "'windows' must be an array"}), 400

    results = []
    for window in windows:
        features = _extract_features(window)
        prediction = dict(predict_cancellation(model, features))
        prediction["schedule_id"] = window.get("schedule_id")
        results.append(prediction)

    log.info(f"Batch prediction: {len(results)} windows processed")

    return jsonify({
        "predictions": results,
        "total":       len(results),
        "model":       "FerryCast AI",
    })


@app.route("/model/stats", methods=["GET"])
def model_stats():
    """Return model metadata and feature importances for the frontend."""
    importances = dict(
        zip(FEATURE_NAMES, [round(float(x), 4) for x in model.feature_importances_])
    )

    # Sort descending by importance
    sorted_imp = dict(sorted(importances.items(), key=lambda x: -x[1]))

    return jsonify({
        "model_type":          "RandomForestClassifier",
        "n_estimators":        model.n_estimators,
        "max_depth":           model.max_depth,
        "n_features":          model.n_features_in_,
        "feature_importances": sorted_imp,
        "training_data_size":  15_000,
    })


@app.route("/retrain", methods=["POST"])
def retrain():
    """Force re-training the model (admin use only)."""
    global model
    log.info("Re-training model on request...")
    start = time.time()
    model = train_model(force_retrain=True)
    elapsed = time.time() - start
    log.info(f"Re-training complete in {elapsed:.2f}s")

    return jsonify({
        "status":  "retrained",
        "elapsed": round(elapsed, 2),
    })


@app.route("/recommend", methods=["POST"])
def recommend():
    """
    Generate multi-modal travel recommendations comparing ferry, bus,
    and flight options for a given origin-destination pair.

    The Laravel backend sends the user's location, destination port
    details, and available ferry schedules. This endpoint runs the
    recommendation engine and returns scored, ranked options.

    Expected JSON body:
    {
        "origin_name":       "Kuala Lumpur",
        "origin_lat":        3.139,
        "origin_lng":        101.687,
        "dest_port_name":    "Tioman Island Marina",
        "dest_port_location": "Tioman Island, Pahang",
        "dest_port_lat":     2.8167,
        "dest_port_lng":     104.1667,
        "ferry_options":     [{...schedule data...}],
        "preference":        "balanced"
    }
    """
    data = request.get_json(silent=True)
    if not data:
        return jsonify({"error": "JSON body required"}), 400

    log.info(
        f"Recommendation request: {data.get('origin_name', '?')} → "
        f"{data.get('dest_port_name', '?')} "
        f"(pref={data.get('preference', 'balanced')})"
    )

    result = recommend_journey(data, model=model, predict_fn=predict_cancellation)

    log.info(f"Returning {result['total_options']} transport options")

    return jsonify(result)


# ---------------------------------------------------------------------------
# Helpers
# ---------------------------------------------------------------------------

def _extract_features(data: dict) -> dict:
    """
    Pull the expected features from a request payload, applying sensible
    defaults for any that are missing.
    """
    now = datetime.now()

    return {
        "wind_speed":     float(data.get("wind_speed", 0)),
        "wave_height":    float(data.get("wave_height", 0)),
        "visibility":     float(data.get("visibility", 10)),
        "wind_direction": float(data.get("wind_direction", 0)),
        "wave_period":    float(data.get("wave_period", 6)),
        "swell_height":   float(data.get("swell_height", 0)),
        "hour_of_day":    int(data.get("hour_of_day", now.hour)),
        "month":          int(data.get("month", now.month)),
    }


# ---------------------------------------------------------------------------
# Entry point
# ---------------------------------------------------------------------------

if __name__ == "__main__":
    port = int(os.environ.get("AI_SERVICE_PORT", 5001))
    log.info(f"Starting FerryCast AI service on port {port}")
    app.run(host="0.0.0.0", port=port, debug=True)
