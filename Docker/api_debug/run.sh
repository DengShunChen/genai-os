#!/bin/bash
set -e
function cleanup() {
    echo "Stopping Python program..."
    kill -SIGINT $PYTHON_PID
    wait $PYTHON_PID
    echo "Python program stopped."
    exit
}
trap cleanup SIGINT
cd /API
apt update
apt install -y curl
while ! curl -s http://web:9000/debug >/dev/null; do
  echo "Waiting for connection to http://web:9000/debug ..."
  sleep 1
done

echo "Connected to http://web:9000/debug"
python3 DummyLLM.py &
PYTHON_PID=$!

wait $PYTHON_PID