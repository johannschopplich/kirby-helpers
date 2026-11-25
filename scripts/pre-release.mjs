import * as fsp from "node:fs/promises";
import { x } from "tinyexec";

const { version } = JSON.parse(await fsp.readFile("package.json", "utf8"));
const composer = JSON.parse(await fsp.readFile("composer.json", "utf8"));
composer.version = version;
await fsp.writeFile(
  "composer.json",
  `${JSON.stringify(composer, undefined, 2)}\n`,
);

await run("composer", ["update"]);
await fsp.rm("vendor", { recursive: true, force: true });
await run("composer", ["dist"]);

const composerEntries = await fsp.readdir("vendor/composer", {
  withFileTypes: true,
});
for (const entry of composerEntries) {
  if (entry.isFile() && entry.name.startsWith("tmp-"))
    await fsp.rm(`vendor/composer/${entry.name}`);
}

await run("git", ["add", "-f", "vendor/"]);

function run(command, args) {
  return x(command, args, {
    throwOnError: true,
    nodeOptions: { stdio: "inherit" },
  });
}
