enum CardTypeEnum {
  birthday = "Birthday",
  christmas = "Christmas",
  valentines_day = "Valentines",
  wedding_anniversary = "Wedding anniversary",
  mothers_day = "Mother's day",
  fathers_day = "Father's day",
  new_baby = "New baby",
  get_well = "Get well",
  thank_you = "Thank you",
  congratulations = "Congratulations",
  break_up = "Break up",
}

enum RecipientEnum {
  friend = "Friend",
  husband = "Husband",
  wife = "Wife",
  mother = "Mother",
  father = "Father",
  daughter = "Daughter",
  son = "Son",
  grandmother = "Grandmother",
  grandfather = "Grandfather",
  granddaughter = "Granddaughter",
  grandson = "Grandson",
  sister = "Sister",
  brother = "Brother",
  aunt = "Aunt",
  uncle = "Uncle",
  cousin = "Cousin",
  nephew = "Nephew",
  niece = "Niece",
  colleague = "Colleague",
  boss = "Boss",
  teacher = "Teacher",
}

enum ThemeEnum {
  none = "None",
  sun_moon_and_stars = "Sun, moon and stars",
  animals = "Animals",
  flowers = "Flowers",
  food = "Food",
  nature = "Nature",
  travel = "Travel",
  music = "Music",
  sports = "Sports",
  star_wars = "Star Wars",
  marvel = "Marvel",
  pokemon = "Pokemon",
}

interface CardOptionsPropsInterfaces {
  occasion: string | keyof typeof CardTypeEnum;
  recipient: string | keyof typeof RecipientEnum;
  make_it_poem: boolean;
  gift?: string; // Enter the gift if you want to mention it in the card content
  topic?: string | keyof typeof ThemeEnum;
}
