#!/bin/python3
# -#- coding: UTF-8 -*-

import logging
import requests
from urllib.parse import urljoin
import atexit
import asyncio

class AgentClient:
    """
    Agent client is responsible to communicate the control signal of the Agent.
    """


    def __init__(self, agent_endpoint: str, llm_name: str, public_endpoint: str):
        """
        Initialize the agent client.
        Arguments:
            agent_endpoint: The root endpoint of the Agent.
            llm_name: The name of this LLM.
            public_endpoint: The public endpoint URI that can be accessed by the Agent.
        """

        self.logger = logging.getLogger(__name__)
        self.agent_endpoint = agent_endpoint
        self.llm_name = llm_name
        self.public_endpoint = public_endpoint

    async def register(self, retry_cnt: int, backoff_time: int = 1):
        """
        Try to registration with the Agent.
        Arguments:
            retry_cnt: The rounds left to retry.
            backoff_time: If this round failed, how may seconds should wait before next round.
        
        Return:
            Return a boolean indicating whether successfully registered.
        """
        
        self.logger.info('Attempting registration with the Agent... {} times left.'.format(retry_cnt))
        try:
            print(self.agent_endpoint, urljoin(self.agent_endpoint, '/worker/register'))
            def do_req():
                return requests.post(
                    urljoin(self.agent_endpoint, './worker/register'),
                    json={
                        'name': self.llm_name,
                        'endpoint': self.public_endpoint
                        }
                    )
            event_loop = asyncio.get_event_loop()
            response = await event_loop.run_in_executor(None, do_req)
            if not response.ok : raise Exception
            else:
                self.logger.info('Registered.')
                atexit.register(self.unregister)
        except Exception as e:
            self.logger.warning('The server failed to register to Agent. Cause: {}.'.format(str(e)))
            
            if retry_cnt != 0:
                self.logger.info('Will retry registration after {} seconds.'.format(backoff_time))
                # Exponential backoff
                await asyncio.sleep(backoff_time)
                return await self.register(retry_cnt-1, backoff_time*2)
            
            else:
                return False

        return True

    def unregister(self):
        """
        Try to unregister with the Agent.

        """

        self.logger.info('Attempting to unregister with the Agent...')
        try:
            response = requests.post(
                urljoin(self.agent_endpoint, './worker/unregister'),
                json={
                    'name': self.llm_name,
                    'endpoint': self.public_endpoint
                    }
            )
            if not response.ok:
                self.logger.warning('Failed to unregister from Agent. Refused by Agent.')
            else:
                self.logger.info('Done.')
        except Exception as e:
            self.logger.warning('Failed to unregister from Agent. Cause: {}.'.format(str(e)))