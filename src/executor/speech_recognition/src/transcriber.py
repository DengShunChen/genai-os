import logging
import whisper_s2t
import functools

logger = logging.getLogger(__name__)

class WhisperS2tTranscriber:
    """
    Encapsulation of WhisperS2T process for multi-processing.
    """

    def __init__(self):
        pass

    @functools.lru_cache
    def load_model(self, name = None, backend = "CTranslate2", enable_word_ts:bool=False):
        if name is None: return None
        model_params = {"asr_options":{"word_timestamps": True}} if enable_word_ts else {}
        model = whisper_s2t.load_model(
            model_identifier=name,
            backend=backend,
            **model_params
        )
        logger.debug(f"Model {name} loaded")
        return model
    
    def transcribe(
        self,
        model_name:str,
        model_backend:str="CTranslate2",
        model_params:dict=None,
        audio_files:list=[],
        **transcribe_kwargs
    ):
        logger.debug("Transcribing...")
        result = None
        try:
            enable_word_ts = model_params.get("word_timestamps", False)
            model = self.load_model(
                name=model_name,
                backend=model_backend,
                enable_word_ts=enable_word_ts,
            )
            if model_params is not None:
                model.update_params({'asr_options': model_params})
            result = model.transcribe_with_vad(audio_files, **transcribe_kwargs)

        except Exception:
            logger.exception("Error when generating transcription")

        logger.debug("Done transcribe.")
        return result