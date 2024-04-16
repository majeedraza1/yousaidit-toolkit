interface StabilityAiEngineInterface {
	id: string;
	name: string;
	description: string;
	type: 'PICTURE';
}

interface SettingInterface {
	apiKey: string;
	engine_id: string;
	style_preset: string;
	imageWidth: number;
	imageHeight: number;
	defaultPrompt: string;
	autoGenerateThumbnail: boolean;
	generateThumbnailFor: string[];
	fileNamingMethod: 'uuid' | 'post_title';
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
};