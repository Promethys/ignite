# MCP Server

## What it is

Ignite ships a [Model Context Protocol](https://modelcontextprotocol.io) server, so an AI client (Claude Desktop, Claude Code, Cursor, or anything else that speaks MCP) can read and manage a user's goals on their behalf. The user brings their own AI; Ignite exposes the operations and enforces who may do what.

It is built on the first-party `laravel/mcp` package. The server class is `App\Mcp\Servers\IgniteServer`, tools live in `app/Mcp/Tools/`, and registration happens in `routes/ai.php`.

Every tool is a thin wrapper over the same service layer the web UI uses (`App\Services\Goals\*`), so an action taken through MCP behaves identically to the same action taken in the browser: same validation, same policies, same business rules.

## Endpoint and transports

```php
// routes/ai.php
Mcp::web('/mcp', IgniteServer::class)->middleware(['auth:sanctum']);
Mcp::local('/ignite', IgniteServer::class);
```

**Remote (HTTP)** is the transport hosted users connect to, at `/mcp` on your instance (for the hosted app: `https://ignite.promethys.dev/mcp`). It is protected by `auth:sanctum`, so every request must carry a personal access token, and by a rate limiter (see [Rate limiting](#rate-limiting)).

**Local (stdio)** runs the server as a subprocess, for self-hosted setups where the client and the app share a machine. There is no HTTP request and therefore no token, so the acting user comes from configuration instead:

```dotenv
MCP_LOCAL_USER=you@example.com
```

The value must be the **email address** of an existing user. It is matched case insensitively. If it is unset, or no user matches it, no tools are exposed over stdio at all.

Because a local user has no token, **no scope restrictions apply to them**: every tool, including the delete tools, is available. That is deliberate, since anyone able to run the process already has database access. Only set `MCP_LOCAL_USER` on a machine where that is acceptable.

## Authentication

Access uses **Laravel Sanctum personal access tokens**. A user creates one under **Settings > API tokens**:

- The plaintext token is shown **once**, at creation, and is never retrievable again. Only a hash is stored.
- Tokens can be listed and revoked at any time. Revoking takes effect immediately.
- Tokens are **immutable**: name and abilities cannot be edited after creation. To change what a token may do, revoke it and create a new one. This is deliberate; see [Security model](#security-model).

The client sends it as a bearer token:

```
Authorization: Bearer <token>
```

## Scopes

Each token carries one or more abilities, chosen at creation:

| Ability | Grants |
| --- | --- |
| `read` | View goals, entries, milestones, and streaks |
| `write` | Create and modify goals, log progress, record check-ins, manage milestones |
| `delete` | Permanently delete goals and progress entries |

`write` implies `read` (both are stored when `write` is selected). **`delete` is independent and must be selected explicitly**, so an everyday token cannot destroy anything.

Scopes are enforced at **registration** time, not at call time: a tool whose required ability the token lacks is not included in the server's tool list at all. It is invisible rather than forbidden. See [Troubleshooting](#troubleshooting) for what that looks like in practice.

## The tool set

Seventeen tools, grouped by the ability they require.

### `read`

| Tool | What it does |
| --- | --- |
| `list_goals` | The user's goals with status, type, progress, streak, and category. Accepts `status`, `type`, `category_id`, `search` (title and description), and `limit` (max 100), and reports the matching `total` |
| `get_goal` | One goal, including its recent entries, ordered milestones, and streak |
| `list_entries` | A goal's progress entries, newest first. Accepts `search` (note text), `from`, `to`, and `limit` (default 50, max 200), and reports the matching `total` |
| `get_user` | The acting user's id, name, timezone, and locale |

### `write`

| Tool | What it does |
| --- | --- |
| `create_goal` | Create a goal. Only `title` and `type` are required (plus `target_value` for a quantifiable goal); operational fields default server side |
| `update_goal` | Partial update. Supply `goal_id` plus only the fields to change; omitted fields keep their current value |
| `complete_goal` | Mark a goal completed and record the completion time |
| `uncomplete_goal` | Revert a completed goal to an active status and clear its completion time. A goal that was never completed is rejected; use `set_goal_status` for an ordinary status change |
| `set_goal_status` | Change a goal's status |
| `log_progress` | Shift a non-recurring goal's current value by an increment and record an entry |
| `check_in` | Record a dated check-in on a recurring goal, one per period, without touching the current value |
| `update_entry` | Edit an entry's increment; the goal's current value shifts by the difference |
| `add_milestone` | Append a milestone to a goal |
| `complete_milestone` | Check off a milestone |
| `set_user` | Partial update of the acting user's own `name`, `timezone`, or `locale` |

`get_user` and `set_user` exist mainly for **timezone correctness**. Check-in dates are validated against "today" in the user's timezone, so a client that does not know the timezone can log check-ins on the wrong day. Neither tool exposes or accepts the account email, password, or two-factor data: the mutable set is deliberately limited to fields whose worst-case misuse is cosmetic. See [Security model](#security-model).

`log_progress` and `check_in` are not interchangeable. Logging progress on a recurring goal, or checking in on a non-recurring one, is rejected with an explanatory message. See [Goal Types](/features/goal-types).

### `delete`

| Tool | What it does |
| --- | --- |
| `delete_goal` | Permanently delete a goal and, by database cascade, all of its entries and milestones |
| `delete_entry` | Permanently delete one progress entry, rewinding the goal's current value for non-recurring goals |

## Destructive operations require confirmation

MCP has no modal dialog, so deletion is a **two-step exchange** handled by `App\Services\Mcp\DestructiveConfirmations`.

**First call**, with no `confirmation_token`: nothing is deleted. The tool returns a preview of exactly what would be destroyed, plus a short-lived token.

```json
{
  "requires_confirmation": true,
  "confirmation_token": "…",
  "preview": "This will permanently delete the goal 'Write a book', its 3 milestones and 2 progress entries. This cannot be undone."
}
```

**Second call**, echoing that token: the deletion is carried out.

A confirmation token is:

- **single use** (consumed on the first attempt, valid or not),
- **short lived** (two minutes),
- **bound to the actor, the operation, and the exact target**, so a token issued to delete one goal cannot delete another, and cannot be replayed against a wider set of targets.

An invalid or expired token returns an error and deletes nothing. Expired and forged tokens are rejected identically, so nothing can be learned by guessing.

Because tokens are held in the cache, the configured cache store must be shared across requests (the `database` driver used in production is). A per-process store such as `array` would break the exchange.

## Rate limiting

The HTTP endpoint carries a `throttle:mcp` limiter, defined in `App\Providers\McpServiceProvider`: **60 requests per minute**, keyed by the **access token**, falling back to the user id and then the client IP.

Keying on the token rather than the user matters: two clients belonging to the same person get independent budgets, so a runaway agent on one token cannot starve the user's other client. Exceeding the limit returns `429`.

## What the tools return

Tool output is built from explicit resource whitelists (`App\Http\Resources\GoalResource`, `GoalEntryResource`, `MilestoneResource`), not from raw models. Only declared fields are ever sent.

This matters because tool output is transmitted to a third-party AI provider. The whitelist means no account data (email, password hash, two-factor secrets) and no internal bookkeeping columns can reach a model, even by accident, and adding a sensitive column to a table later cannot leak it retroactively.

Lists stay lean: `list_goals` omits entries and milestones, while `get_goal` and the goal-writing tools include them. So that multi-step progress is still visible in the lean list, every goal carries a `milestone_summary` (`{total, completed}`), computed with a counting query rather than by loading the relation.

`progress_percentage` is reported for every goal type that has a meaningful notion of progress. A quantifiable goal measures it against `target_value`; a multi-step goal measures it as the share of its steps that are completed. It is `null` when there is nothing to measure, which covers a recurring goal (progress there is the streak), a simple goal, and a multi-step goal with no steps yet.

**Lists never truncate silently.** `list_goals` and `list_entries` both return the matching `total` alongside the capped slice and its effective `limit`, and say so in their text (`Retrieved 20 of 137 progress entries.`). A `limit` of `null` means nothing was capped. A client can therefore tell that more exist rather than mistaking a truncated slice for the whole history and reporting it as fact.

Every goal carries its `category` as `{id, name}`, or `null` when it has none. Without it the `category_id` filter would be unusable: an AI client has no other way to learn which ids exist.

## Connecting a client

### Over HTTP

1. In Ignite, go to **Settings > API tokens** and generate a token with the abilities you want. Copy it immediately.
2. Point your MCP client at the HTTP endpoint with that token as a bearer credential. For Claude Code:

```bash
claude mcp add --transport http ignite https://ignite.promethys.dev/mcp \
  --header "Authorization: Bearer <token>"
```

3. Reconnect the client and confirm the Ignite tools appear.

### Over stdio, on your own machine

1. Set `MCP_LOCAL_USER` in `.env` to the email of the user the server should act as.
2. Register the server as a subprocess. The client starts it; you do not run it yourself:

```bash
claude mcp add ignite-local -- php artisan mcp:start ignite
```

`ignite` is the handle registered by `Mcp::local()` in `routes/ai.php`. The command must resolve from the project directory, since the subprocess inherits the working directory; use an absolute path to `artisan` if your client starts elsewhere.

This transport talks over stdin and stdout, so nothing needs to be listening on a port and `php artisan serve` is irrelevant to it.

### Trying it without a client

```bash
php artisan mcp:inspector mcp
```

It starts a browser UI against `/mcp`. The app itself must be running and reachable (`php artisan serve`), and you still need to supply the `Authorization` header in the inspector's auth panel. Pass the local handle instead (`mcp:inspector ignite`) to inspect the stdio server.

## Security model

- **Per-user isolation.** Every operation resolves through the acting user and is authorized by the existing policies. A goal id belonging to someone else is rejected, so a hallucinated or tampered id cannot reach another user's data.
- **The acting user is never client supplied.** `create_goal` assigns ownership from the token holder and ignores any user id in the payload.
- **Scoped tokens**, with `delete` opt-in, plus the two-step confirmation on destructive tools.
- **Whitelisted output**, as described above.
- **Normalized input.** A web request is trimmed by middleware before it is validated; an MCP request has no middleware stack, so tools trim incoming strings themselves and treat a whitespace-only value as a field that was not supplied. Without this, a blank title or name would pass validation untouched and be stored, since Laravel skips non-implicit rules for any value that trims to empty.
- **Untrusted content.** Goal titles and notes are user-authored text that reaches the model as data. The server instructions tell the model to treat them as data rather than instructions, and no tool can change account credentials, so a prompt-injection attempt cannot escalate into account takeover. This is why `set_user` cannot touch the email address: email is the account-recovery vector, and changing it would also unverify the account and lock the user out of the web app.
- **Run production with `APP_DEBUG=false`.** Unhandled exceptions are logged server side and returned to the client as a generic message. With debug enabled, raw exception text is returned instead, which would expose internals to the AI client.

## Troubleshooting

**Some tools are missing from my client.**
Almost always a scope mismatch. Because scopes are enforced at registration, a token without an ability makes the corresponding tools *invisible* rather than returning a permission error. A client with a `read`-only token that is asked to delete something will answer that it has no such tool. Check the token's abilities under **Settings > API tokens**, and if they are wrong, revoke it and create a new one with the abilities you need.

**I changed my token's abilities, or upgraded Ignite, and nothing changed.**
Clients fetch the tool list once, when they connect, and cache it. Restarting Ignite itself changes nothing on the client side. Reconnect the MCP server so the client requests the list again; if the tools still do not appear, remove the server from your client's configuration and add it back, which forces a fresh handshake rather than reusing held connection state.

To check whether the problem is the client or the server, ask the server directly:

```bash
curl -s -X POST https://ignite.promethys.dev/mcp \
  -H "Authorization: Bearer <token>" \
  -H "Content-Type: application/json" \
  -H "Accept: application/json, text/event-stream" \
  -d '{"jsonrpc":"2.0","id":"1","method":"tools/list"}'
```

If a tool appears there but not in your client, the client's cache is stale.

**Deletion never completes.**
Deletion needs two calls. If the second call reports an invalid token, the confirmation may have expired (two minutes), already been used, or been issued for a different target. Ask for a fresh preview and confirm again.

## How to use it

- Generate a token under **Settings > API tokens** with the smallest set of abilities that fits the job. Prefer `read` for a purely analytical assistant, `read` plus `write` for day-to-day tracking, and add `delete` only when you actually want an AI able to remove data.
- Connect your MCP client to `/mcp` on your instance with that token.
- Ask in natural language: "how am I doing on my goals?", "log 5 km on my running goal", "check in on my writing habit for yesterday".
- Revoke tokens you no longer use. Revocation is immediate and there is no way to edit a token in place.
