# AI Assistant

## What it is

The assistant works with an AI account the user or the operator brings. Ignite never pays for a model: each user saves their own API key in **Settings > AI Assistant**, or a self-hosted instance provides one configuration for everybody through `.env`.

This page covers how keys are stored and chosen. Without a key and without an instance configuration, the assistant is not offered at all.

## Supported providers

A user can save one key for each of these providers, which all authenticate with a single API key:

| Provider   | Value in `assistant_keys.provider` |
| ---------- | ---------------------------------- |
| Anthropic  | `anthropic`                        |
| DeepSeek   | `deepseek`                         |
| Gemini     | `gemini`                           |
| Groq       | `groq`                             |
| Mistral    | `mistral`                          |
| OpenAI     | `openai`                           |
| OpenRouter | `openrouter`                       |
| xAI        | `xai`                              |

The list is the `App\Enums\AssistantProvider` enum. Each value is a provider name in `config/ai.php`, the configuration file of the [Laravel AI SDK](https://laravel.com/docs/13.x/ai-sdk), which sends the requests.

Providers that need more than a key (Azure OpenAI, Amazon Bedrock, an OpenAI-compatible endpoint, a local Ollama server) are available to self-hosters through the [instance configuration](#instance-configuration), not in the settings form.

How well the assistant uses its tools depends on the model. Each saved key has an optional model field; left blank, the provider's cheapest text model as defined by the SDK is used.

## Saved keys

Keys live in the `assistant_keys` table, one row per user and provider (`App\Models\AssistantKey`).

- **Encrypted like the rest of the user's text.** `api_key` is encrypted with the user's own data key (see [Data Encryption](/features/encryption)), so it is unreadable from a database copy and is destroyed with the account.
- **Never sent back to the browser.** The settings page receives only each key's provider, model, whether it is the one in use, and `key_suffix`, the last four characters stored in clear at save time. `api_key` is hidden from serialization.
- **Checked before it is saved.** `App\Services\Assistant\AssistantKeyChecker` calls an endpoint of the provider that costs nothing (the model list; for OpenRouter, the key details endpoint, because its model list is public). A key the provider refuses is rejected with a field error; a provider that cannot be reached is reported as such, not as a wrong key. Live field validation never calls the provider.
- **Consent per key.** Saving a key requires acknowledging that goals and notes are sent to that provider under the user's own account. `consented_at` records it.

### One key in use

A user can keep keys for several providers, and exactly one is in use while they have any. The rules live on the model:

- The first key a user saves is the one in use.
- `markAsDefault()` switches to another key and clears the flag on the rest in one transaction.
- Removing the key in use promotes the most recently added remaining key.

Removing a key deletes Ignite's copy only. Revoking the key itself is done in the provider's account.

## Instance configuration

A self-hosted instance can provide the assistant to all of its users. Set the default provider and that provider's credentials in `.env`:

```ini
AI_DEFAULT_PROVIDER=anthropic
ANTHROPIC_API_KEY=...
```

A local [Ollama](https://ollama.com) server needs no key:

```ini
AI_DEFAULT_PROVIDER=ollama
OLLAMA_URL=http://localhost:11434
```

Any text provider in `config/ai.php` works here. The instance counts as configured only when `AI_DEFAULT_PROVIDER` names a provider and that provider has a key, or is one that does not use a key (`ollama`, `openai-compatible`, `bedrock`). See [Configuration](/configuration#ai-assistant) for the variables.

Ollama's default model is very small and unlikely to handle tool calling well. Set a stronger one in `config/ai.php` under the provider's `models.text.cheapest`.

## Which configuration is used

`App\Services\Assistant\AssistantKeyResolver` decides, for one user:

1. The user's key in use, with the provider's settings from `config/ai.php` and the user's model if they set one.
2. Otherwise the instance configuration, when there is one.
3. Otherwise nothing.

The shared Inertia prop `assistant.available` tells the frontend whether one of the first two applies. It is computed without reading or decrypting any key, and carries neither a key nor a provider name.
