import resend
from app.common.constant import RESEND_API_KEY
from copy import deepcopy


class SendMail:
    def __init__(self):
        resend.api_key = RESEND_API_KEY

    def _send(self, to_mail: str, mail_content: dict, **kwargs):
        content = deepcopy(mail_content)
        emails = [email.strip() for email in to_mail.split(",") if email.strip()]
        content["to"] = emails

        html_template = content.pop("html")
        content["html"] = html_template.format(**kwargs)
     
        resend.Emails.send(content)