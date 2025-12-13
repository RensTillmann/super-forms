# Input Group Component Documentation

## Overview

The Input Group component allows you to "Display additional information or actions to an input or textarea." It's a flexible wrapper that combines inputs with supplementary content like icons, text, and buttons.

## Installation

```bash
pnpm dlx shadcn@latest add input-group
```

## Core Components

The Input Group system consists of these composable parts:

- **InputGroup**: Main wrapper container
- **InputGroupInput**: Replacement for standard Input with pre-applied styles
- **InputGroupTextarea**: Replacement for standard Textarea
- **InputGroupAddon**: Container for icons, text, buttons, or other content
- **InputGroupButton**: Buttons displayed within groups
- **InputGroupText**: Text labels and information

## Usage Patterns

### Basic Structure

```jsx
import {
  InputGroup,
  InputGroupAddon,
  InputGroupInput,
} from "@/components/ui/input-group"

<InputGroup>
  <InputGroupInput placeholder="Search..." />
  <InputGroupAddon>
    <SearchIcon />
  </InputGroupAddon>
</InputGroup>
```

### Alignment Options

The `align` prop positions addons relative to inputs:

- **inline-start / inline-end**: Use with InputGroupInput for horizontal positioning
- **block-start / block-end**: Use with InputGroupTextarea for vertical positioning

## Common Examples

### With Icons

Display search, mail, or validation icons alongside inputs:

```jsx
<InputGroup>
  <InputGroupInput type="email" placeholder="Enter your email" />
  <InputGroupAddon>
    <MailIcon />
  </InputGroupAddon>
</InputGroup>
```

### With Text Affixes

Add prefixes and suffixes like currency symbols or domains:

```jsx
<InputGroup>
  <InputGroupAddon>
    <InputGroupText>$</InputGroupText>
  </InputGroupAddon>
  <InputGroupInput placeholder="0.00" />
  <InputGroupAddon align="inline-end">
    <InputGroupText>USD</InputGroupText>
  </InputGroupAddon>
</InputGroup>
```

### With Buttons

Enable actions like copy, search, or submission:

```jsx
<InputGroup>
  <InputGroupInput placeholder="Type to search..." />
  <InputGroupAddon align="inline-end">
    <InputGroupButton variant="secondary">
      Search
    </InputGroupButton>
  </InputGroupAddon>
</InputGroup>
```

### With Tooltips

Provide contextual help through tooltips:

```jsx
<InputGroup>
  <InputGroupInput placeholder="Enter password" type="password" />
  <InputGroupAddon align="inline-end">
    <Tooltip>
      <TooltipTrigger asChild>
        <InputGroupButton variant="ghost" size="icon-xs">
          <InfoIcon />
        </InputGroupButton>
      </TooltipTrigger>
      <TooltipContent>
        Password must be at least 8 characters
      </TooltipContent>
    </Tooltip>
  </InputGroupAddon>
</InputGroup>
```

### With Textarea

Combine with textarea for multi-line inputs and add controls at block-end:

```jsx
<InputGroup>
  <InputGroupTextarea placeholder="Enter your message" />
  <InputGroupAddon align="block-end">
    <InputGroupButton>Send</InputGroupButton>
  </InputGroupAddon>
</InputGroup>
```

### With Spinner

Show loading states during processing:

```jsx
<InputGroup data-disabled>
  <InputGroupInput placeholder="Searching..." disabled />
  <InputGroupAddon align="inline-end">
    <Spinner />
  </InputGroupAddon>
</InputGroup>
```

### With Dropdown

Integrate dropdown menus for filtering or options:

```jsx
<InputGroup>
  <InputGroupInput placeholder="Enter search query" />
  <InputGroupAddon align="inline-end">
    <DropdownMenu>
      <DropdownMenuTrigger asChild>
        <InputGroupButton variant="ghost">
          Search In... <ChevronDownIcon />
        </InputGroupButton>
      </DropdownMenuTrigger>
      <DropdownMenuContent>
        <DropdownMenuItem>Documentation</DropdownMenuItem>
      </DropdownMenuContent>
    </InputGroupAddon>
  </InputGroupAddon>
</InputGroup>
```

### Custom Input

Use the `data-slot="input-group-control"` attribute on custom inputs for automatic styling:

```jsx
<InputGroup>
  <TextareaAutosize
    data-slot="input-group-control"
    className="flex field-sizing-content min-h-16 w-full resize-none..."
    placeholder="Autoresize textarea..."
  />
  <InputGroupAddon align="block-end">
    <InputGroupButton>Submit</InputGroupButton>
  </InputGroupAddon>
</InputGroup>
```

## API Reference

### InputGroup Props

| Prop | Type | Default |
|------|------|---------|
| `className` | string | — |

### InputGroupAddon Props

| Prop | Type | Default |
|------|------|---------|
| `align` | "inline-start" \| "inline-end" \| "block-start" \| "block-end" | "inline-start" |
| `className` | string | — |

**Note**: Place addons after inputs for proper focus navigation. Addons can contain multiple buttons and icons.

### InputGroupButton Props

| Prop | Type | Default |
|------|------|---------|
| `size` | "xs" \| "icon-xs" \| "sm" \| "icon-sm" | "xs" |
| `variant` | "default" \| "destructive" \| "outline" \| "secondary" \| "ghost" \| "link" | "ghost" |
| `className` | string | — |

### InputGroupInput Props

Inherits all standard input props. Pre-applies input group styling.

### InputGroupTextarea Props

Inherits all standard textarea props. Pre-applies textarea group styling.

## Recent Updates

A `min-w-0` class was added to InputGroup (2025-10-06) to improve overflow handling in flex layouts.
