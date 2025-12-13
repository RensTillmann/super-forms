# shadcn/ui Input Component Documentation

## Overview

The Input component is a form control that "displays a form input field or a component that looks like an input field."

## Installation

```bash
pnpm dlx shadcn@latest add input
```

## Basic Usage

```typescript
import { Input } from "@/components/ui/input"

export function InputDemo() {
  return <Input type="email" placeholder="Email" />
}
```

## Examples

### Default Input
A standard email input with placeholder text.

### File Input
```typescript
import { Input } from "@/components/ui/input"
import { Label } from "@/components/ui/label"

export function InputFile() {
  return (
    <div className="grid w-full max-w-sm items-center gap-3">
      <Label htmlFor="picture">Picture</Label>
      <Input id="picture" type="file" />
    </div>
  )
}
```

### Disabled State
```typescript
export function InputDisabled() {
  return <Input disabled type="email" placeholder="Email" />
}
```

### With Label
```typescript
export function InputWithLabel() {
  return (
    <div className="grid w-full max-w-sm items-center gap-3">
      <Label htmlFor="email">Email</Label>
      <Input type="email" id="email" placeholder="Email" />
    </div>
  )
}
```

### With Button
```typescript
import { Button } from "@/components/ui/button"

export function InputWithButton() {
  return (
    <div className="flex w-full max-w-sm items-center gap-2">
      <Input type="email" placeholder="Email" />
      <Button type="submit" variant="outline">
        Subscribe
      </Button>
    </div>
  )
}
```

## Recent Changes

**2025-09-18:** Remove `flex` class — The flex class was removed from the input component as it "is no longer needed."
