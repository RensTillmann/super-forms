---
name: l-test-basic-demo
branch: none
status: pending
created: 2025-10-30
---

# Basic Test Demo

## Problem/Goal
Create a basic test.md file for demonstration purposes to learn the cc-sessions task creation workflow.

## Success Criteria
- [ ] Task file created with proper cc-sessions structure
- [ ] Successfully walked through the task creation protocol
- [ ] Understood how task files work in cc-sessions workflow

## Context Manifest

### How cc-sessions Task Management Works

This is a demonstration task to help you understand the cc-sessions workflow used in the Super Forms project. The cc-sessions system provides a structured approach to task management with automated protocols and mode-based editing controls.

**Task File Structure:**

Every task in cc-sessions follows a consistent YAML frontmatter + Markdown structure:

1. **Frontmatter** (lines 1-6): Contains metadata including:
   - `name`: Task identifier with prefix (e.g., `l-test-basic-demo`)
   - `branch`: Git branch strategy (`feature/`, `fix/`, `experiment/`, or `none`)
   - `status`: Current state (`pending`, `in-progress`, `completed`, `blocked`)
   - `created`: Creation date in YYYY-MM-DD format

2. **Problem/Goal Section**: Clear description of what needs to be solved or built

3. **Success Criteria**: Checklist of specific, measurable outcomes

4. **Context Manifest**: Auto-populated by the context-gathering agent (this section)

5. **User Notes**: Optional developer-specific notes or requirements

6. **Work Log**: Timestamped progress updates

**DAIC Mode System:**

The repository uses Discussion-Alignment-Implementation-Check workflow:

- **Discussion Mode** (default): Edit/Write tools are blocked. Focus is on planning and discussing approach. This prevents accidental code changes during planning phases.
- **Implementation Mode**: Activated by user trigger phrase "yert". Tools become available to execute approved work.

**Task Workflow Trigger Phrases:**

The user controls workflow transitions with configured phrases:
- `mek:` - Create new task
- `start^` - Begin working on a task
- `finito` - Mark task as complete
- `squish` - Compact context to save tokens
- `yert` - Enable implementation mode
- `SILENCE` - Return to discussion mode

**Session Organization:**

Located in `/root/go/src/github.com/RensTillmann/super-forms/sessions/`:
- `tasks/` - All task markdown files
- `TEMPLATE.md` - Template for new tasks
- `CLAUDE.sessions.md` - Collaboration guidelines and philosophy
- `sessions-config.json` - Configuration including trigger phrases
- `protocols/` - Automated workflow protocols
- `transcripts/` - Conversation history

**Super Forms Project Context:**

Super Forms is a WordPress drag & drop form builder plugin. Key points for task work:
- Main source in `/src/` directory
- WordPress Coding Standards apply
- Prefix functions/classes with `super`, `SUPER`, or `sfui`
- React components use `npm run watch` for development
- Testing requires both frontend form rendering and admin functionality verification

### Technical Reference Details

#### Task File Location
- Current task: `/root/go/src/github.com/RensTillmann/super-forms/sessions/tasks/l-test-basic-demo.md`
- Template: `/root/go/src/github.com/RensTillmann/super-forms/sessions/tasks/TEMPLATE.md`

#### Key Configuration
- Developer name: Rens
- Shell: bash on Linux
- Branch enforcement: enabled
- Task detection: enabled
- Default branch: main
- Auto-merge and auto-push: enabled

#### Collaboration Philosophy
From `CLAUDE.sessions.md`:
- **Investigate patterns** - Look for existing examples before creating new patterns
- **Confirm approach** - Explain reasoning and get consensus before proceeding
- **Locality of Behavior** - Keep related code close together
- **Solve Today's Problems** - Avoid excessive abstraction for hypothetical future issues
- **Readability > Cleverness** - Code should be obvious and easy to follow

This demonstration task requires no actual code changes - it's purely for learning the cc-sessions workflow structure and understanding how tasks are organized, tracked, and executed in this repository.

## User Notes
<!-- Any specific notes or requirements from the developer -->

## Work Log
<!-- Updated as work progresses -->
- [YYYY-MM-DD] Started task, initial research
