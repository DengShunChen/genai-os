# -#- coding: UTF-8 -*-
import time, requests, sys, socket, os, openai, signal
from flask import Flask, request, Response
from flask_sse import ServerSentEventsBlueprint

def handler(signum, frame):
    print("Received SIGTERM, exiting...")
    if registered:
        try:
            response = requests.post(agent_endpoint + f"{version_code}/worker/unregister", data={"name":LLM_name,"port":port})
            if response.text == "Failed":
                print("Warning, Failed to unregister from agent")
        except requests.exceptions.ConnectionError as e:
            print("Warning, Failed to unregister from agent")
    print("exited")
    sys.exit(0)

signal.signal(signal.SIGTERM, handler)

os.environ["CUDA_VISIBLE_DEVICES"] = "1"
app = Flask(__name__)
app.config["REDIS_URL"] = "redis://localhost:6379/0"
sse = ServerSentEventsBlueprint('sse', __name__)
app.register_blueprint(sse, url_prefix='/')
# -- Configs --
agent_endpoint = "http://localhost:9000/"
LLM_name = "chatgpt"
model = "gpt-3.5-turbo-0613"
version_code = "v1.0"
ignore_agent = False
limit = 1024*3
model_loc = "gpt-3.5-turbo-0613"
# This is the IP that will be stored in Agent, 
# Make sure the IP address here are accessible by Agent
ignore_agent = False
port = None # By choosing None, it'll assign an unused port
dummy = False
# -- Config ends --

if port == None:
    with socket.socket() as s:
        port = s.bind(('', 0)) or s.getsockname()[1]

Ready = [True]
if not dummy:
    # model part
    def process(data):
        try:
            chatgpt_apitoken = data.get("chatgpt_apitoken")
            msg = [i['msg'] for i in eval(data.get("input").replace("true","True").replace("false","False"))]
            
            if msg and chatgpt_apitoken:
                msg = msg[-1].strip()
                chatgpt_apitoken = chatgpt_apitoken.strip()
                if len(msg) > 0 and len(chatgpt_apitoken) > 0:
                    openai.api_key = chatgpt_apitoken
                    yield openai.ChatCompletion.create(model=model,
                          max_tokens=2000,
                          temperature=0.5,
                          messages=[
                          {"role": "user", "content": msg}
                        ]).choices[0].message.content
                    openai.api_key = None
                else:
                    yield "No chatgpt token are received!" if len(msg) > 0 else "No input message are received!"
            else:
                yield "No chatgpt token are received!" if msg else "No input message are received!"
        except Exception as e:
            print(e)
            if str(e).startswith("Incorrect API key provided:"):
                yield "Incorrect API Key, You should provide a correct API key to use this LLM!"
            else:
                yield str(e)
        finally:
            Ready[0] = True
            print("finished")
    # model part ends
else:
    def process(data): 
        try:
            for i in "The crisp morning air tickled my face as I stepped outside. The sun was just starting to rise, casting a warm orange glow over the cityscape. I took a deep breath in, relishing in the freshness of the morning. As I walked down the street, the sounds of cars and chatter filled my ears. I could see people starting to emerge from their homes, ready to start their day.":
                yield i
                time.sleep(0.02)
        except Exception as e:
            print(e)
        finally:
            Ready[0] = True
            print("finished")

@app.route("/", methods=["POST"])
def api():
    if Ready[0]:
        Ready[0] = False
        resp = Response(process(request.form), mimetype='text/event-stream')
        resp.headers['Content-Type'] = 'text/event-stream; charset=utf-8'
        if request.form.get("input"): return resp
        print("I didn't see your input!")
        Ready[0] = True
    return ""
registered = True
response = requests.post(agent_endpoint + f"{version_code}/worker/register", data={"name":LLM_name,"port":port})
if response.text == "Failed":
    print("Warning, The server failed to register to agent")
    registered = False
    if not ignore_agent:
        print("The program will exit now.")
        sys.exit(0)
else:
    print("Registered")

if __name__ == '__main__':
    app.run(port=port, host="0.0.0.0")
    if registered:
        try:
            response = requests.post(agent_endpoint + f"{version_code}/worker/unregister", data={"name":LLM_name,"port":port})
            if response.text == "Failed":
                print("Warning, Failed to unregister from agent")
        except requests.exceptions.ConnectionError as e:
            print("Warning, Failed to unregister from agent")