# AI Assistant

## What it is

The assistant works with an AI account the user or the operator brings. Ignite never pays for a model: each user saves their own API key in **Settings > AI Assistant**, or a self-hosted instance provides one configuration for everybody through `.env`.

With one of the two in place, a button in the page header opens a chat panel. The assistant answers questions about the user's goals and changes them on request, through the same tools as the [MCP server](/features/mcp-server). Without a key and without an instance configuration, the assistant is not offered at all.

For how to use it, see [Use the assistant](/guide/assistant).

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

## The chat

### The agent

`App\Ai\Agents\IgniteAssistant` is built for each request with the signed-in user, the resolved configuration and, when the user is on a goal page, that goal.

- **Instructions.** The MCP server's own instructions (`IgniteServer::INSTRUCTIONS`), followed by the user's language, their timezone and today's date there. The goal on screen is passed as an id only, never as text.
- **Scope.** The agent is told to help only with goals, progress, habits, milestones, categories, planning and how Ignite itself works, and to decline anything else in one sentence. This is an instruction to the model, not a filter: how well it holds depends on the model.
- **Steps.** One message can run at most eight model steps, tool calls included.
- **Model.** The user's model when their key sets one, otherwise the provider's cheapest text model.

### Tools

`App\Ai\AssistantTools` gives the agent every tool in `IgniteServer::TOOLS` that the signed-in user may use, with the same names, descriptions and schemas as over MCP. Each tool validates its own arguments and applies only to the current user's data. Arguments a tool refuses are returned to the model as text so it can correct itself.

One of them, `get_help`, returns a page of the [user guide](/guide/), so the assistant explains how Ignite works from the guide rather than from what the model assumes.

### Deletions need approval

`delete_goal`, `delete_entry` and `delete_category` are wrapped in `App\Ai\Tools\ConfirmedDeletion`:

1. The model calls the delete tool with the target only. The confirmation token of the [MCP flow](/features/mcp-server#destructive-operations-require-confirmation) is hidden from the tool's schema, so the model never sees or supplies one.
2. The run pauses and the panel shows the preview of what would be deleted, with **Keep it** and **Delete**.
3. On **Delete**, the tool gets a fresh token on the server and deletes. On **Keep it**, nothing runs and the model is told the call was refused.

A token the model makes up is ignored. A target that fails validation (for example another user's goal) never asks for approval and fails when run.

### Endpoints

All routes sit behind `auth` and `verified`, and only ever touch the signed-in user's conversations. Another user's conversation id answers 404.

| Route                                 | Purpose                                                       |
| ------------------------------------- | ------------------------------------------------------------- |
| `POST assistant/chat`                 | Send a message or an approval decision; the reply is streamed |
| `GET assistant/conversations`         | The 30 most recent conversations                              |
| `GET assistant/conversations/{id}`    | One conversation with its last 100 messages                   |
| `PATCH assistant/conversations/{id}`  | Rename (title of at most 100 characters)                      |
| `DELETE assistant/conversations/{id}` | Delete the conversation and its messages                      |

`POST assistant/chat` takes:

- `messages`: the new user message, or the assistant message carrying the approval decisions. Only text is read; attachments are dropped. A message is at most 4000 characters.
- `conversation_id`: omitted to start a conversation. The id of the conversation in use is returned in the `X-Conversation-Id` response header.
- `goal_id`: the goal on screen. A goal that is not the user's is ignored.

It answers 404 when the user has no key and the instance has no configuration, and is limited to 20 requests per minute per user. The reply is a server-sent event stream in the [AI SDK UI message stream](https://ai-sdk.dev/docs/ai-sdk-ui/stream-protocol) format. An error raised while streaming reaches the browser as a generic error part, never as the provider's message.

### Conversations

Conversations are stored in the AI SDK's `agent_conversations` and `agent_conversation_messages` tables, with the user as participant. The stored history, not the browser, is what the model receives on the next message.

Titles, message text, tool calls with their arguments and results, and provider error messages are encrypted with the user's own data key, like the rest of their text (see [Data Encryption](/features/encryption#in-the-assistant-s-conversations)). The [admin panel](/features/admin-panel) has no page over them. A user can delete a conversation from the panel, and deleting the account deletes all of them.

A conversation's title is the first 50 characters of its first message. No model call is made to generate it (`ai.conversations.generate_title` is `false`), and the user can rename it.

### The panel

`resources/js/components/assistant/AssistantPanel.vue` is loaded on demand, only for users with `assistant.available`. `useAssistantChat` wraps `useChat` from `@ai-sdk/vue`:

- It posts only the newest message, with the conversation id and the goal on screen.
- After a write or a deletion completes, it reloads the page's props so the screen behind the panel is current.
- Replies are rendered as Markdown with raw HTML escaped and images disabled, so a reply cannot load a remote address.
- Tool calls appear as one short line each. Their arguments and results are not displayed.

## What is sent where

Each message sends the provider the agent's instructions, the conversation so far, the tool definitions and whatever the tools returned during the turn (goal titles, notes, entries, categories). It goes from the Ignite server to the provider chosen by [the configuration in use](#which-configuration-is-used), under that key. Nothing is sent until the user writes a message.
