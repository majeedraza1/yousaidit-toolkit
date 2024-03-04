interface TextOptionInterface {
  font_family: string;
  font_size: string;
  alignment: 'left' | 'center' | 'right';
  color: string;
}

interface InnerMessagePropsInterface extends TextOptionInterface {
  message: string;
  type?: 'video' | 'text' | '';
  video_id?: number;
}

interface OpenAiOptionInterface {
  occasion: string,
  recipient: string,
  topic: string,
  custom_topic: string,
  poem: boolean
}

interface InnerMessageCartItemPropsInterface {
  type: 'text' | 'video';
  content: string;
  font: string;
  align: string;
  color: string;
  size: string;
  video_id: number;
  aws_job_id?: string;
}

interface InnerMessageCartItemDataInterface {
  key: string;
  variation?: {
    attribute_pa_size: string;
    [key: string]: string;
  };
  _card_size: string;
  _inner_message?: InnerMessageCartItemPropsInterface,
  _video_inner_message?: InnerMessageCartItemPropsInterface,
}

export type {
  TextOptionInterface,
  InnerMessagePropsInterface,
  OpenAiOptionInterface,
  InnerMessageCartItemPropsInterface,
  InnerMessageCartItemDataInterface
}