FROM pytorch/pytorch:2.2.0-cuda12.1-cudnn8-runtime

WORKDIR /app

COPY dependencies.txt .
RUN pip install --no-cache-dir -r dependencies.txt

COPY . .

CMD ["python", "app.py"]
