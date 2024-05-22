interface LoginInfoInterface {
  name: string;
  email: string;
}

interface BrandInfoInterface {
  username: string;
  brand_name: string;
  brand_location: string;
  brand_instagram_url: string;
  brand_details: string;
}

export type {
  LoginInfoInterface,
  BrandInfoInterface
}