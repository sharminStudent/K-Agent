# K-Agent System Specifications

This document reflects the current confirmed direction of the `k-agent` project as a SaaS platform and distinguishes implemented scope from planned scope.

## 1. Product Direction

K-Agent is being built as a SaaS platform where each company manages its own AI agent configuration, knowledge base, leads, and chat history. The current repository is not a finished SaaS platform yet. It is at the backend foundation stage.

## 2. Current Confirmed Architecture

### 2.1 Platform Type

- Multi-tenant SaaS by company ownership model.
- The tenant boundary is currently represented by `agent_id`.
- A user belongs to one company agent.
- Core business records are scoped to the owning agent/company.

### 2.2 Backend Stack

- Laravel 13
- PHP 8.3
- PostgreSQL configured in the local environment
- Vite present
- Tailwind present

### 2.3 Implemented Core Data Domains

- `agents`
- `chat_sessions`
- `chat_messages`
- `leads`
- `knowledge_files`
- `users.agent_id`

### 2.4 Implemented Ownership and Access Rules

- Authenticated users can create one company agent when they do not already own one.
- Users can only view or update their own agent configuration.
- Users can only regenerate widget tokens for their own agent.
- Public widget-facing flows resolve the company by `widget_token`.
- Chat, lead, and knowledge records are stored against the resolved `agent_id`.

## 3. Implemented Backend Capabilities

### 3.0 Filament Admin

Implemented:

- Filament admin panel and authentication
- Custom branded login screen
- Tenant-scoped sidebar/navigation structure
- Dashboard with widgets for setup, company stats, conversation trends, account snapshot, recent chats, recent leads, and workspace users
- Agent settings section
- Leads section
- Chat logs section with transcript viewer/download
- Knowledge management section
- General Settings section with Profile view and Profile edit flow

Not implemented:

- Dashboard preferences page for configurable user settings
- Rich reporting/export features across dashboard sections
- Full create/upload workflows from Filament for every domain

### 3.1 Agent Configuration API

Implemented:

- Create agent
- View agent
- Update agent
- Regenerate widget token

Available company/agent fields currently include:

- Agent name
- Company name
- Slug
- Website URL
- Industry
- Company description
- Contact email
- Support email
- Support phone
- System prompt
- Welcome message
- Fallback message
- Settings JSON
- Active status

### 3.2 Chat Backend

Implemented:

- Create chat session by `widget_token`
- Store visitor message by `widget_token` and session public id
- Persist chat sessions and user messages

Not implemented:

- AI-generated assistant response
- OpenAI chat completion flow
- Streaming responses
- Conversation quality scoring

### 3.3 Lead Capture

Implemented:

- Lead storage endpoint
- Agent-scoped lead persistence
- Optional session linkage
- Filament lead management UI with view/edit/filter support

Not implemented:

- Lead qualification workflow
- Export

### 3.4 Knowledge Upload and Processing

Implemented:

- Upload endpoint
- File validation
- File storage
- Metadata persistence
- Text extraction for TXT, CSV, JSON, and DOCX
- Chunk generation
- Ingestion status transitions: `pending`, `processing`, `ready`, `failed`
- Filament knowledge review UI

Not implemented:

- Embeddings generation
- Vector database integration
- Retrieval pipeline

## 4. Confirmed Missing Major SaaS Areas

The following areas are not yet implemented in the current repository:

- Subscription and billing model
- Role model beyond the current single-owner pattern
- End-to-end OpenAI response generation in the chat runtime
- End-to-end RAG retrieval integrated into chat responses
- Guardrails and fallback analytics integrated into runtime behavior
- Widget frontend
- Realtime streaming
- Deployment strategy and operations plan

## 5. Current SaaS Interpretation

The correct reading of current progress is:

- The project already has tenant-aware backend groundwork.
- The project already has a working Filament admin/dashboard shell for internal/company use.
- The project does not yet have the AI runtime or customer-facing widget experience.
- The repository is suitable for continuing toward a SaaS platform, but it should not be described as feature-complete or launch-ready.

## 6. Verified Testing Status

The current feature test suite passes locally.

Covered areas:

- Agent API
- Chat API
- Lead API
- Knowledge API

Current local result at verification time:

- `19` tests passed
- `70` assertions passed

## 7. Recommended Next Build Order

To move from backend foundation to actual SaaS platform, the next implementation order should be:

1. Update project documentation and tracker files to reflect the current Filament/admin state.
2. Implement OpenAI chat response generation end-to-end in the chat flow.
3. Implement embeddings, vector storage, and retrieval, then connect RAG to chat responses.
4. Add guardrails, fallback tracking, and platform logging.
5. Add widget frontend and session continuity UX.
6. Define deployment, queue, and production environment strategy.

## 8. Notes

- The repository does not currently contain a prior `k-agent-system-specification.md` file.
- This file is created to reflect the current repo state and the SaaS direction now being followed.
