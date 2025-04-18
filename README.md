CSE427S_FinalProject

Link to original site: http://ec2-3-136-83-96.us-east-2.compute.amazonaws.com/~leew1488/cse427_finalproject/news.php


To run the site, pull these Docker images - 

Docker image for ML: https://hub.docker.com/repository/docker/lwonsang/cse427s_finalproject-ml/general

Docker image for Site: https://hub.docker.com/repository/docker/lwonsang/cse427s_finalproject-web/general

using these commands:
docker pull lwonsang/cse427s_finalproject-web
docker pull lwonsang/cse427s_finalproject-ml

Then we need to create a .env file in the NewsSharingApp folder.

Finally, run:
docker-compose up

The site will be on http://localhost:8090

When you post a new story, the genre will be automatically predicted using Machine Learning, look at news.php for more information

Use this command to test out ML:
curl -X POST http://localhost:5000/predict \
     -H "Content-Type: application/json" \
     -d '{"input": " Insert text here"}'
