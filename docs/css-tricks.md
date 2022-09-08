# CSS Tricks

**Examples:**

- [Change checkbox/radio layout to vertical on mobile devices](#change-checkbox-radio-layout-to-vertical-on-mobile-devices)

## Change checkbox/radio layout to vertical on mobile devices

```css
// You can change 800px to something more suitable for your needs if needed
@media (max-width: 800px) {
  .super-form .display-grid .super-items-list {
    flex-direction: column!important;
    .super-item {
      width: 100%!important;
    }
  }
}
```
