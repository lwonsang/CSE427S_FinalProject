from transformers import AutoTokenizer, AutoModelForSequenceClassification

tokenizer = AutoTokenizer.from_pretrained("WebOrganizer/TopicClassifier-NoURL")
model = AutoModelForSequenceClassification.from_pretrained(
    "WebOrganizer/TopicClassifier-NoURL",
    trust_remote_code=True,
    use_memory_efficient_attention=False)


# Insert Input Text Here
web_page = """I love to travel!"""

inputs = tokenizer([web_page], return_tensors="pt")
outputs = model(**inputs)

probs = outputs.logits.softmax(dim=-1)
print(probs.argmax(dim=-1))

