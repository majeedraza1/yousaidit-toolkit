interface LoginInfoInterface {
  name: string;
  email: string;
  password: string;
}

interface BrandInfoInterface {
  brand_name: string;
  brand_location: string;
  brand_profile_url: string;
  brand_instagram_url: string;
  brand_details: string;
}

export type {
  LoginInfoInterface,
  BrandInfoInterface
}