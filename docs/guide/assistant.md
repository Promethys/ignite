# Use the Assistant

Ignite has a built-in assistant that can answer questions about your goals and update them for you. It runs on an AI account that you bring: Ignite does not include one.

## Connect an AI account

1. Create an API key in the account you have with one of the supported providers: Anthropic, DeepSeek, Gemini, Groq, Mistral, OpenAI, OpenRouter or xAI.
2. In Ignite, open **Settings > AI Assistant**, choose the provider and paste the key.
3. Confirm that you understand your goals and notes are sent to that provider when you use the assistant, then save. Ignite checks the key before keeping it.

Your key is stored encrypted and is deleted with your account. You can save a key for more than one provider and choose which one is in use. The optional model field lets you pick a stronger model than the provider's cheapest one, which helps if the assistant struggles with a request.

On a self-hosted instance, the operator may already have configured an AI account for everyone. In that case the assistant works without a key of your own.

## Open the panel

Once an account is connected, a sparkle button appears at the top right of every page. It opens the assistant in a side panel.

Write your message and press Enter. Shift and Enter add a new line.

## What you can ask

The assistant works with what Ignite holds: goals, progress, check-ins, milestones and categories.

- "How am I doing this month?"
- "Log 5 kilometres on my running goal for yesterday."
- "Create a goal to read twelve books by the end of the year."
- "Which goals are overdue?"

When you are on a goal's page, "this goal" means the one on screen.

It is meant for your goals only and will decline unrelated requests.

A short line appears each time the assistant reads or changes something. When it changes your data, the page behind the panel updates.

## Deletions ask you first

The assistant cannot delete a goal, an entry or a category on its own. It shows what would be deleted and waits. Choose **Delete** to go ahead or **Keep it** to cancel. Nothing is removed until you choose.

## Your conversations

Conversations are kept so you can come back to them. The panel reopens the latest one.

- **New chat** starts a fresh conversation.
- **Past conversations** lists the earlier ones. Select one to reopen it.
- The pencil renames a conversation, the bin deletes it with its messages.

## Good to know

- The assistant uses your AI account, so each message counts against that account's usage and cost.
- What you write and the goal data the assistant reads to answer you are sent to the provider you connected.
- An AI model can misread a request. Check what it changed, and fix or delete an entry from the goal page if needed.
- Removing your key in **Settings > AI Assistant** hides the assistant. To revoke the key itself, do it in your provider's account.
