# -#- coding: UTF-8 -*-
# This demonstrated how to pipe the output of llm into another llm before returning the result.
import time, re, requests, sys, socket, os, torch
from flask import Flask, request, Response
from flask_sse import ServerSentEventsBlueprint
os.environ["CUDA_VISIBLE_DEVICES"] = "0"
app = Flask(__name__)
app.config["REDIS_URL"] = "redis://192.168.211.4:6379/0"
sse = ServerSentEventsBlueprint('sse', __name__)
app.register_blueprint(sse, url_prefix='/')
# -- Configs --
agent_endpoint = "http://192.168.211.4:9000/"
LLM_name = "llama2-7b-chat-b1.0.0"
# This is the IP that will be stored in Agent,
# Make sure the IP address here are accessible by Agent
version_code = "v1.0"
ignore_agent = False
limit = 1024*3
model_loc = "llama2-7b-chat-b1.0.0"
port = None # By choosing None, it'll assign an unused port
dummy = False
api_key = "uwU123DisApikEyiSASeCRetheHehee"
usr_token = "92d1e9d60879348b8ed2f25f624012dcc596808dc40681d74c4965b8fff8a22a"
tc_model = 26
# -- Config ends --

if port == None:
    with socket.socket() as s:
        port = s.bind(('', 0)) or s.getsockname()[1]

Ready = [True]
if not dummy:
    # model part
    from transformers import AutoModelForCausalLM, AutoConfig, AutoTokenizer, StoppingCriteria, StoppingCriteriaList, pipeline
    
    class StopOnTokens(StoppingCriteria):
        def __call__(self, input_ids: torch.LongTensor, scores: torch.FloatTensor, **kwargs) -> bool:
            for stop_ids in stop_token_ids:
                if torch.all(input_ids[0][-len(stop_ids):] == stop_ids):
                    return True
            return False

    
    model = AutoModelForCausalLM.from_pretrained(model_loc,
        config=AutoConfig.from_pretrained(model_loc),device_map="auto",torch_dtype=torch.float16)
    model.eval()
    tokenizer = AutoTokenizer.from_pretrained(model_loc)
    stop_list = ['[INST]', '\nQuestion:', "[INST: ]"]
    stop_token_ids = [torch.LongTensor(tokenizer(x)['input_ids']).to('cuda') for x in stop_list]
    pipe = pipeline(model=model, tokenizer=tokenizer,return_full_text=True,
        task='text-generation',stopping_criteria=StoppingCriteriaList([StopOnTokens()]),
        temperature=0.2,max_new_tokens=2048,repetition_penalty = 1.0, do_sample=True)
    prompts = "[INST] {0} [/INST]\n{1}\n"
    def process(data):
        try:
            history = [i['msg'] for i in eval(data.replace("true","True").replace("false","False"))]
            history.append("")
            history = [prompts.format(history[i], history[i + 1]) for i in range(0, len(history), 2)]
            history = "<s> " + "".join(history)
            result = pipe(history)[0]['generated_text']
            print(result)
            for i in result[len(history):]:
                yield i
                time.sleep(0.02)

            torch.cuda.empty_cache()

        except Exception as e:
            print(e)
        finally:
            torch.cuda.empty_cache()
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
        data = request.form.get("input")
        resp = Response(process(data), mimetype='text/event-stream')
        resp.headers['Content-Type'] = 'text/event-stream; charset=utf-8'
        if data: return resp
        print("I didn't see your input!")
        Ready[0] = True
    return ""
registered = True
response = requests.post(agent_endpoint + f"{version_code}/worker/register", data={"version":version_code,"name":LLM_name,"port":port})
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