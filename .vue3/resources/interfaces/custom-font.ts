interface FontInfoInterface {
  slug: string;
  font_family: string;
  font_file: string;
  group: string;
  for_public: boolean;
  for_designer: boolean;
  url: string;
  path: string;
}

interface PreInstalledFontInterface extends FontInfoInterface {

}

interface ExtraFontInterface extends FontInfoInterface {

}

export type {
  FontInfoInterface,
  PreInstalledFontInterface,
  ExtraFontInterface
}