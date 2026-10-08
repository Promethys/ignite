<?php

namespace App\Enums;

enum AssistantProvider: string
{
    case Anthropic = 'anthropic';
    case DeepSeek = 'deepseek';
    case Gemini = 'gemini';
    case Groq = 'groq';
    case Mistral = 'mistral';
    case OpenAI = 'openai';
    case OpenRouter = 'openrouter';
    case xAI = 'xai';

    public function label(): string
    {
        return match ($this) {
            AssistantProvider::Anthropic => 'Anthropic',
            AssistantProvider::DeepSeek => 'DeepSeek',
            AssistantProvider::Gemini => 'Gemini',
            AssistantProvider::Groq => 'Groq',
            AssistantProvider::Mistral => 'Mistral',
            AssistantProvider::OpenAI => 'OpenAI',
            AssistantProvider::OpenRouter => 'OpenRouter',
            AssistantProvider::xAI => 'xAI',
        };
    }
}
