FROM python:3.10-alpine

WORKDIR /usr/src/app

# COPY src/kernel/requirements.txt ./
# RUN pip install --no-cache-dir -r requirements.txt

COPY src/kernel/. .
RUN pip install --no-cache-dir -r requirements.txt

CMD [ "kuwa-kernel" ]