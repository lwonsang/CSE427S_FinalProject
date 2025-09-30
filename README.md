**CSE427S_FinalProject**

Link to Demo Video: https://drive.google.com/file/d/17kQx_aC1wWD276SvXpU8RX3oEjq9unuT/view?usp=sharing

Current Prototype Figma Design:
<img width="1323" height="848" alt="Screenshot 2025-09-29 at 9 37 14 PM" src="https://github.com/user-attachments/assets/55a36960-ddbd-4739-ace4-4262d066612e" />


Original Prototype:
<img width="512" height="251" alt="unnamed" src="https://github.com/user-attachments/assets/6259cc0f-0863-4343-b3a2-8d08f25eb8fa" />



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
