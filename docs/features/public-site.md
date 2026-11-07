# Public Site

## Pages and routes

The public site uses Inertia pages inside `PublicShell`, with its own color tokens, header and footer. It is available to guests and signed-in users.

| Route           | Page                                    |
| --------------- | --------------------------------------- |
| `/`             | Product overview                        |
| `/features`     | Features and examples                   |
| `/self-host`    | Self-hosting overview                   |
| `/docs`         | Getting Started, guide cards and search |
| `/docs/{topic}` | A guide article                         |

The named routes `docs.index` and `docs.show` point to the guide. `/docs/getting-started` redirects permanently to `/docs` with HTTP 301. An unknown topic returns HTTP 404 inside the docs layout, with links back to the guide and search. Each page supplies its title and description through the public shell's Inertia metadata.

## Guide source

`docs/guide/*.md` is the source for the in-app guide, the MCP `get_help` tool and the `ignite://guide/*` resources. `App\Services\Help\UserGuide` reads the files, extracts their titles and summaries, and provides the page order. `index.md` is the `getting-started` topic and appears at `/docs`.

The guide content is English, including when the app is set to French. Navigation, search, buttons and error messages use the app's English and French translations in `lang/{en,fr}/public.php`.

## Rendering and sanitisation

`App\Services\Help\GuideRenderer` renders Markdown on the server with League CommonMark and GitHub-flavored tables. Raw HTML is stripped and unsafe link schemes are disabled. Heading IDs support direct links, and the H2 and H3 headings supply the article's "On this page" navigation. Code blocks have a client-side copy button.

The renderer recognises these markers on the first line of a blockquote:

```markdown
> [!NOTE]
> Additional context.

> [!TIP]
> A useful suggestion.

> [!WARNING]
> Something to check before continuing.
```

They render as styled callouts. Other blockquotes keep normal Markdown behaviour.

## Links and images

The renderer adapts root-relative links to their destination:

- `/guide/` links to `/docs`.
- `/guide/<topic>` links to the matching `/docs/<topic>` article.
- Other root-relative page links, such as `/getting-started` or `/features/mcp-server`, link to `https://ignite-docs.promethys.dev`.
- Guide images use `/images/guide/<name>.png`, served from `public/images/guide` in the app.

Article footers link to the raw Markdown on GitHub and the source file's GitHub edit page. Previous and next links follow the guide's page order.

## Search

The server supplies an index containing each article's title, summary, headings and plain body text, limited to 20,000 characters per article. Search matches all query words in that index in the browser and shows matching articles or an empty state. When all query words match a heading, the result shows that heading and links directly to its anchor. It does not use an external search service.

## Add a guide page

1. Add a Markdown file in `docs/guide`, with a filename suitable for a URL topic.
2. Start it with one H1 title and a short opening paragraph for the summary.
3. Use H2 and H3 headings for sections that should appear in the article navigation.
4. Put images in `public/images/guide` and reference them with `/images/guide/` paths. Use `/guide/<topic>` for links to other guide pages.
5. Check the article, search results and previous/next navigation at `/docs`. The same file is available through `get_help` and the guide resources.

## Developer documentation

VitePress publishes developer documentation only. Its `srcExclude` omits `guide/**`, and the "Using Ignite" navigation link opens the app's `/docs` page. The VitePress Markdown and edit links continue to refer to developer pages.

`docs/public/_redirects` redirects old `/guide` and `/guide/*` URLs to the app with HTTP 301 responses. Run `npm run docs:build` after changing developer documentation to check internal links.
