# Categories

## What it is

A category is a per-user grouping for goals (`Category belongsTo User`, `Category hasMany Goal`). Categories are never shared across users; each one is scoped to the account that created it. There is no setting to turn categories on or off, they're a standard part of the goal model.

## Default categories on registration

When a new user registers, the `Registered` event fires and the `GenerateDefaultUserCategories` listener creates ten default categories for that user, each with a name, a color, and a Lucide icon name:

Health & Fitness, Career & Work, Finance & Money, Learning & Education, Personal Development, Relationships & Social, Hobbies & Creativity, Home & Lifestyle, Travel & Adventure, Wellness & Mental Health.

These are just a starting point: the user can rename, delete, or add their own categories afterward like any other category they own.

## Slug and order (CategoryObserver)

`Category` uses the `Sluggable` trait (`cviebrock/laravel-sluggable`), configured to derive the slug from `name`. Sluggable hooks into the model's `saving` event, which fires before `creating`, so by the time `CategoryObserver::creating()` runs, a slug is normally already set. The observer still guards for the case where one isn't: if `slug` is empty, it generates one from `name` via `Str::slug()`.

The observer also assigns display order on creation: if no `order` is given and the category has a `user_id`, `order` is set to `(the user's current highest category order) + 1`. This keeps newly created categories appended at the end of the user's list rather than defaulting to `0`.

## Colors and icons

Each category stores a `color` (hex string, used for visual tagging) and an `icon` (a Lucide icon name, matching how goals reference their own `icon`). Both are plain, freeform fields set through the category form; there's no fixed palette or icon set enforced server-side.

## How to use it

- Categories are managed from the Categories pages (`CategoryController`): list, create, edit, delete, each scoped to the authenticated user via policy checks.
- A goal optionally belongs to one category (`category_id`, nullable, `set null` on category delete rather than cascading), assigned from the goal's own create/edit form.
- On the goals list, filtering by category happens client-side: the page loads the user's full goal list, and picking a category in the filter (backed by the `?category=` query parameter, which also seeds the initial selection) narrows what's shown without a new request to the server.
