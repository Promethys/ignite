<?php

namespace App\Enums;

enum AssistantProvider: string
{
    case Anthropic = 'anthropic';
    case DeepSeek = 'deepseek';
    case Gemini = 'gemini';
    case Groq = 'groq';
    case Mistral = 'mistral';
    case OpenAi = 'openai';
    case OpenRouter = 'openrouter';
    case XAi = 'xai';

    public function label(): string
    {
        return match ($this) {
            AssistantProvider::Anthropic => 'Anthropic',
            AssistantProvider::DeepSeek => 'DeepSeek',
            AssistantProvider::Gemini => 'Gemini',
            AssistantProvider::Groq => 'Groq',
            AssistantProvider::Mistral => 'Mistral',
            AssistantProvider::OpenAi => 'OpenAI',
            AssistantProvider::OpenRouter => 'OpenRouter',
            AssistantProvider::XAi => 'xAI',
        };
    }
}
