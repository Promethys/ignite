export interface GuideHeading {
    id: string;
    text: string;
    level: number;
}

export interface GuidePage {
    topic: string;
    title: string;
    summary: string;
    url: string;
}

export interface GuideSearchPage extends GuidePage {
    text: string;
    headings: GuideHeading[];
}

export interface GuideArticle {
    topic: string;
    title: string;
    summary: string;
    html: string;
    headings: GuideHeading[];
    rawUrl: string;
    editUrl: string;
}

export interface DocsPageProps {
    pages: GuidePage[];
    searchIndex: GuideSearchPage[];
    article: GuideArticle | null;
    previous: GuidePage | null;
    next: GuidePage | null;
}
