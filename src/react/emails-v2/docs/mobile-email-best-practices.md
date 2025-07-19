# Mobile Email Design Best Practices (2025)

## Font Sizes

### Body Text
- **Minimum**: 14px (absolute minimum)
- **Recommended**: 16-18px
- **Apple Guidelines**: 17-22px
- **Google Guidelines**: 18-22px
- **iPhone Constraint**: Nothing below 13px (will be auto-scaled and may break layout)

### Headings
- **Recommended**: 22px or larger

### Line Height
- **Body Text**: 1.4 to 1.6 times the font size
- **Example**: 16px font = 22-26px line height

## Spacing Guidelines

### Padding
- **Between Elements**: 10-20px (top/bottom)
- **Mobile-Specific**: Consider increasing padding on mobile for better touch targets

### Touch Targets (CTAs/Buttons)
- **Minimum Size**: 44x44px (Apple) to 48x48px (Google)
- **Recommended**: 46x46px (compromise between Apple and Google)
- **Safe Zone**: 10-20px extra space around clickable areas
- **Note**: Avoid placing multiple text links next to each other

## Container Width
- **Desktop**: 600-700px (industry standard)
- **Mobile**: 100% width with appropriate padding
- **Email Preview Pane**: Maximum 600px to ensure proper display

## Implementation Strategy

### Current vs. Recommended
Currently in our email builder:
- Desktop: 600px container (✓ Good)
- Mobile: 100% width container (✓ Good)

### Recommended Updates:
1. **Font Size Adjustments**
   - Increase minimum body font to 16px on mobile
   - Ensure headings are at least 22px
   
2. **Padding Adjustments**
   - Add responsive padding that increases on mobile
   - Desktop: 20px padding
   - Mobile: 24-32px padding

3. **Button/CTA Updates**
   - Ensure all buttons are at least 46x46px on mobile
   - Add proper padding around clickable elements

4. **Line Height**
   - Set line-height to 1.5 for better readability

## Media Query Structure

```css
/* Desktop styles (default) */
.email-container {
  width: 600px;
  padding: 20px;
}

.email-text {
  font-size: 16px;
  line-height: 24px;
}

/* Mobile styles */
@media only screen and (max-width: 600px) {
  .email-container {
    width: 100% !important;
    padding: 24px !important;
  }
  
  .email-text {
    font-size: 16px !important;
    line-height: 24px !important;
  }
  
  .email-button {
    min-width: 46px !important;
    min-height: 46px !important;
    padding: 12px 24px !important;
  }
}
```

## Dark Mode Considerations
- Use transparent images where possible
- Ensure proper color contrast
- Test in both light and dark modes

## Testing Requirements
- Test on actual devices (not just browser dev tools)
- Check major email clients (Gmail, Outlook, Apple Mail)
- Verify touch targets are easily tappable
- Ensure text remains readable without zooming