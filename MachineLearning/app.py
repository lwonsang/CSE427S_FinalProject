from transformers import AutoTokenizer, AutoModelForSequenceClassification

taxonomydict = {
    0:"Adult",
    1:"Art & Design",
    2 : "Software Dev",
    3 : "Crime & Law",
    4 : "Education & Jobs",
    5 : "Hardware",
    6 : "Entertainment",
    7 : "Social Life",
    8 : "Fashion & Beauty",
    9 : "Finance & Business",
    10 : "Food & Dining",
    11 : "Games",
    12 : "Health",
    13 : "History",
    14 : "Home & Hobbies",
    15 : "Industrial",
    16 : "Literature",
    17 : "Politics",
    18 : "Religion",
    19 : "Science & Tech.",
    20 : "Software",
    21 : "Sports & Fitness",
    22 : "Transportation",
    23 : "Travel"
}


def setup():
    tokenizer = AutoTokenizer.from_pretrained("WebOrganizer/TopicClassifier-NoURL")
    model = AutoModelForSequenceClassification.from_pretrained(
        "WebOrganizer/TopicClassifier-NoURL",
        trust_remote_code=True,
        use_memory_efficient_attention=False)
    print("Setup Complete.")
    return tokenizer, model

def process_model(input, tokenizer, model):
    inputs = tokenizer([input], return_tensors="pt")
    outputs = model(**inputs)

    probs = outputs.logits.softmax(dim=-1)
    return probs.argmax(dim=-1)



def main():
    tokenizer, model = setup()
    print("Ready for input. Type 'exit' to quit.")
    while True:
        try:
            user_input = input("Enter something: ")
            if user_input.lower() in {"exit", "quit"}:
                print("Exiting.")
                break
            output = process_model(user_input, tokenizer, model)
            print(taxonomydict[output.item()])
        except EOFError:
            # Handles end-of-file (e.g. Ctrl+D or closed input)
            break
        except Exception as e:
            print(f"Error: {e}")


        

if __name__ == "__main__":
    main()

