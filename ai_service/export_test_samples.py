import os
import pandas as pd
from sklearn.model_selection import train_test_split
from model import generate_training_data, FEATURE_NAMES
import joblib

# Ensure we're in the correct directory
MODEL_DIR = os.path.join(os.path.dirname(__file__), "trained_model")
MODEL_PATH = os.path.join(MODEL_DIR, "route_cancellation_rf.pkl")

def export_test_samples():
    print("Generating the synthetic data with the same random seed...")
    df = generate_training_data(n_samples=100_000, seed=42)
    
    X = df[FEATURE_NAMES]
    y = df["cancelled"]

    print("Splitting to isolate the 20,000 test samples...")
    X_train, X_test, y_train, y_test = train_test_split(
        X, y, test_size=0.2, random_state=42, stratify=y
    )

    print("Loading the trained model...")
    if not os.path.exists(MODEL_PATH):
        print(f"Error: Model not found at {MODEL_PATH}. Please run model.py first.")
        return

    model = joblib.load(MODEL_PATH)
    
    print("Running predictions on the test set...")
    predictions = model.predict(X_test)
    probabilities = model.predict_proba(X_test)[:, 1]

    # Combine features, actual labels, and predictions into one DataFrame
    results_df = X_test.copy()
    results_df["actual_cancelled"] = y_test
    results_df["predicted_cancelled"] = predictions
    results_df["cancellation_probability"] = [round(p, 4) for p in probabilities]

    # Determine if the model was correct
    results_df["is_correct"] = results_df["actual_cancelled"] == results_df["predicted_cancelled"]

    # Save to CSV
    output_path = os.path.join(os.path.dirname(__file__), "test_samples_results.csv")
    results_df.to_csv(output_path, index=False)
    
    print(f"\nSuccess! Exported {len(results_df)} test samples to: {output_path}")
    print("You can open this CSV file to manually inspect the features vs. predictions.")

if __name__ == "__main__":
    export_test_samples()
