import os
from datetime import datetime

version = "v1.0"
data = {}
log_folder = "logs"
record_file = "records.pickle"

log_file_path = os.path.join(log_folder, datetime.now().strftime('%Y-%m-%d_%H-%M-%S.%f') + '.log')