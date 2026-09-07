import { $, fs } from "zx";

const { version } = await fs.readJson("./package.json");
const composer = await fs.readJson("./composer.json");
composer.version = version;
await fs.writeJson("./composer.json", composer, { spaces: 2 });
await $`composer update`;
await fs.remove("./vendor");
await $`composer dist`;
await $`find vendor/composer -maxdepth 1 -type f -name 'tmp-*' -delete`;
await $`git add -f vendor/`;
