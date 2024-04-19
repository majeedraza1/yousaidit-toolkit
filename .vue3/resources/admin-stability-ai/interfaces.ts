interface StabilityAiEngineInterface {
  id: string;
  name: string;
  description: string;
  type: 'PICTURE';
}

interface SettingInterface {
  api_key: string;
  engine_id: string;
  style_preset: string;
  max_allowed_images_for_guest_user: number;
  max_allowed_images_for_auth_user: number;
  remove_images_after_days: number;
  default_prompt: string;
  file_naming_method: 'uuid' | 'post_title';
}

interface SettingResponseInterface {
  editable: boolean;
  message: string;
  settings: SettingInterface;
  engines: StabilityAiEngineInterface[];
  style_presets: string[];
}

export type {
  SettingInterface,
  SettingResponseInterface,
  StabilityAiEngineInterface
};