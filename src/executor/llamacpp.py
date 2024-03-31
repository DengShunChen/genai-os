import os
import sys
import logging
import time
import json
from typing import Optional
sys.path.append(os.path.dirname(os.path.abspath(__file__)))
from llama_cpp import Llama

from kuwa.executor import LLMWorker

logger = logging.getLogger(__name__)

class LlamaCppWorker(LLMWorker):

    model_path: Optional[str] = None
    limit: int = 1024*3
    
    def __init__(self):
        super().__init__()

    def _create_parser(self):
        parser = super()._create_parser()
        parser.add_argument('--limit', type=int, default=self.limit, help='The limit of the context window')
        parser.add_argument('--model_path', default=self.model_path, help='Model path')
        parser.add_argument('--gpu_config', default=None, help='GPU config')
        parser.add_argument('--ngl', type=int, default=0, help='Number of layers to offload to GPU. If -1, all layers are offloaded')
        return parser

    def _setup(self):
        super()._setup()

        if self.args.gpu_config:
            os.environ["CUDA_VISIBLE_DEVICES"] = self.args.gpu_config

        self.model_path = self.args.model_path
        if not self.model_path:
            raise Exception("You need to configure a .gguf model path!")

        if not self.LLM_name:
            self.LLM_name = "gguf"

        self.model = Llama(model_path=self.model_path, n_gpu_layers=self.args.ngl)
        self.serving_generator = None

    async def llm_compute(self, data):
        try:
            s = time.time()
            history = [i['msg'] for i in json.loads(data.get("input"))]
            while len("".join(history)) > self.limit:
                del history[0]
                if history: del history[0]
            if len(history) != 0:
                history.append("")
                history = ["<s>[INST] {0} [/INST]{1}".format(history[i], ("{0}" if i+1 == len(history) - 1 else " {0} </s>").format(history[i + 1])) for i in range(0, len(history), 2)]
                history = "".join(history)
                output_generator = self.model.create_completion(
                    history,
                    max_tokens=4096,
                    stop=["</s>"],
                    echo=False,
                    stream=True
                )
                self.serving_generator = output_generator
                
                for i in output_generator:
                    if self.in_debug(): print(end=i["choices"][0]["text"], flush=True)
                    yield i["choices"][0]["text"]
            else:
                yield "[Sorry, The input message is too long!]"

        except Exception as e:
            logger.error("Error occurs while processing request.")
            raise e
        finally:
            logger.debug("finished")
    
    async def abort(self):
        if not self.serving_generator:
            return "There's not running generation request to abort."
        self.serving_generator.close()
        self.serving_generator = None
        logger.debug("aborted")
        return "Aborted"

if __name__ == "__main__":
    worker = LlamaCppWorker()
    worker.run()