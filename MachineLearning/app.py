from flask import Flask, request, jsonify
from transformers import AutoTokenizer, AutoModelForSequenceClassification

app = Flask(__name__)

taxonomydict = {
    0: "Adult",
    1: "Art & Design",
    2: "Software Dev",
    3: "Crime & Law",
    4: "Education & Jobs",
    5: "Hardware",
    6: "Entertainment",
    7: "Social Life",
    8: "Fashion & Beauty",
    9: "Finance & Business",
    10: "Food & Dining",
    11: "Games",
    12: "Health",
    13: "History",
    14: "Home & Hobbies",
    15: "Industrial",
    16: "Literature",
    17: "Politics",
    18: "Religion",
    19: "Science & Tech.",
    20: "Software",
    21: "Sports & Fitness",
    22: "Transportation",
    23: "Travel"
}

tokenizer = AutoTokenizer.from_pretrained("WebOrganizer/TopicClassifier-NoURL")
model = AutoModelForSequenceClassification.from_pretrained(
    "WebOrganizer/TopicClassifier-NoURL",
    trust_remote_code=True,
    use_memory_efficient_attention=False
)

@app.route("/predict", methods=["POST"])
def predict():
    data = request.get_json()
    input_text = data.get("input", "")
    if not input_text:
        return jsonify({"error": "No input provided"}), 400

    inputs = tokenizer([input_text], return_tensors="pt")
    outputs = model(**inputs)
    probs = outputs.logits.softmax(dim=-1)
    prediction = taxonomydict[probs.argmax(dim=-1).item()]

    return jsonify({"prediction": prediction})

if __name__ == "__main__":
    app.run(host="0.0.0.0", port=5000)
