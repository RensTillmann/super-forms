# Email Builder Architecture PRD
## Universal Element Wrapper & Email Generation System

**Version**: 1.0  
**Date**: 2024-07-17  
**Status**: RFC - Request for Comments

---

## Executive Summary

This PRD outlines a comprehensive redesign of the Super Forms email builder architecture to address two critical needs:

1. **Universal Element Wrapper**: A capability-based system that eliminates code duplication and provides consistent behavior across all element types
2. **Email Generation System**: A robust pipeline that generates email-client-compatible HTML from our React builder components

### Key Objectives
- ✅ Eliminate code duplication across element components
- ✅ Enable flexible element capabilities (resizable, backgrounds, etc.)
- ✅ Ensure 100% email client compatibility (Outlook, Gmail, Apple Mail, etc.)
- ✅ Maintain modern builder UX while generating legacy-compatible email HTML
- ✅ Support responsive design patterns that work across all email clients

---

## Current Architecture Problems

### ❌ Code Duplication
```javascript
// Repeated in every element component
isSelected && 'ev2-ring-2 ev2-ring-green-500 ev2-animate-pulse'
```

### ❌ Inconsistent Implementation
- Each element handles selection state differently
- Spacing/margin logic scattered across components
- No standardized capability system

### ❌ Email Generation Gap
- React components use modern CSS (flexbox, grid)
- Email clients require table-based layouts
- No clear path from builder → email HTML

---

## Proposed Architecture

### 🏗️ Component Hierarchy
```
SortableElement.jsx (drag/drop + actions)
  └── UniversalElementWrapper.jsx (capabilities + selection + spacing)
      └── ElementRenderer.jsx (routing)
          └── Individual Elements (pure content rendering)
              └── EmailTemplateGenerator.jsx (HTML table output)
```

### 🎯 Separation of Concerns

**Builder Components** (React + Modern CSS):
- Rich interactive builder experience
- Modern CSS (flexbox, grid, transforms)
- Real-time preview and editing

**Email Templates** (HTML Tables + Inline CSS):
- Maximum email client compatibility
- Table-based layouts (100% support)
- Inline styles (Gmail compatibility)

---

## Element Capability System

### 🔧 Capability Definition
Elements define their capabilities through a standardized schema:

```javascript
// Element capability definition
const elementCapabilities = {
  text: {
    resizable: { horizontal: true, vertical: false },
    background: { color: true, image: false },
    spacing: { margin: true, padding: true, border: true },
    typography: { font: true, size: true, color: true },
    alignment: { horizontal: true, vertical: false }
  },
  
  section: {
    resizable: { horizontal: true, vertical: true },
    background: { color: true, image: true },
    spacing: { margin: true, padding: true, border: true },
    container: { droppable: true, columns: true },
    layout: { fullWidth: true, contained: true }
  },
  
  button: {
    resizable: { horizontal: true, vertical: false },
    background: { color: true, image: false },
    spacing: { margin: true, padding: true, border: true },
    interactive: { href: true, tracking: true },
    typography: { font: true, size: true, color: true }
  }
}
```

### 🎛️ UniversalElementWrapper Implementation
```javascript
function UniversalElementWrapper({ element, isSelected, capabilities }) {
  const elementCaps = capabilities[element.type] || {};
  
  return (
    <div className={`universal-element-wrapper ${isSelected ? 'selected' : ''}`}>
      {/* Selection & Interaction Layer */}
      {isSelected && <SelectionRing />}
      
      {/* Capability-Based Controls */}
      {elementCaps.resizable?.horizontal && <ResizeHandle direction="horizontal" />}
      {elementCaps.background?.image && <BackgroundImageControl />}
      
      {/* Spacing Layer */}
      <SpacingLayer 
        margin={element.props.margin}
        border={element.props.border}
        padding={element.props.padding}
        background={element.props.background}
      >
        {/* Pure Element Content */}
        <ElementRenderer element={element} />
      </SpacingLayer>
    </div>
  );
}
```

### 📱 Responsive Capability System
```javascript
const responsiveCapabilities = {
  breakpoints: ['mobile', 'tablet', 'desktop'],
  
  // Element-specific responsive behavior
  text: {
    mobile: { fontSize: 'scale-down', lineHeight: 'compact' },
    tablet: { fontSize: 'normal', lineHeight: 'normal' },
    desktop: { fontSize: 'normal', lineHeight: 'normal' }
  },
  
  section: {
    mobile: { columns: 1, padding: 'compact' },
    tablet: { columns: 2, padding: 'normal' },
    desktop: { columns: 'flexible', padding: 'spacious' }
  }
}
```

---

## Email Generation System

### 📧 Email Client Compatibility Matrix

| Feature | Gmail | Outlook | Apple Mail | Yahoo | Support Strategy |
|---------|-------|---------|------------|-------|------------------|
| CSS Grid | ❌ | ❌ | ✅ | ❌ | **Use Tables** |
| Flexbox | ⚠️ | ❌ | ✅ | ⚠️ | **Progressive Enhancement** |
| Media Queries | ⚠️ | ❌ | ✅ | ✅ | **Fluid Design + Table Stacking** |
| Background Images | ✅ | ❌ | ✅ | ✅ | **VML Fallback for Outlook** |
| Web Fonts | ✅ | ⚠️ | ✅ | ✅ | **System Font Fallbacks** |

### 🏗️ Table-Based Layout Generation

**React Builder** → **Email HTML Generator** → **Client-Compatible Output**

```javascript
// Builder Component (Modern CSS)
<div className="section-element" style={{ display: 'flex', gap: '20px' }}>
  <div className="column">Content 1</div>
  <div className="column">Content 2</div>
</div>

// Generated Email HTML (Table Layout)
<table role="presentation" cellspacing="0" cellpadding="0" border="0" width="100%">
  <tr>
    <td style="padding-right: 10px; vertical-align: top; width: 50%;">
      Content 1
    </td>
    <td style="padding-left: 10px; vertical-align: top; width: 50%;">
      Content 2
    </td>
  </tr>
</table>
```

### 📐 Responsive Email Patterns

#### Full-Width Header with Contained Body
```html
<!-- Full-width header -->
<table role="presentation" width="100%" cellspacing="0" cellpadding="0">
  <tr>
    <td bgcolor="#007acc" style="padding: 20px 0;">
      <table align="center" width="600" cellspacing="0" cellpadding="0">
        <tr>
          <td style="text-align: center; color: white;">
            Header Content
          </td>
        </tr>
      </table>
    </td>
  </tr>
</table>

<!-- Contained body -->
<table align="center" width="600" cellspacing="0" cellpadding="0">
  <tr>
    <td style="padding: 40px 20px;">
      Body Content
    </td>
  </tr>
</table>
```

#### Mobile-First Responsive Columns
```html
<table role="presentation" cellspacing="0" cellpadding="0">
  <tr>
    <!--[if mso]>
    <td width="300" valign="top">
    <![endif]-->
    <div style="display: inline-block; width: 100%; max-width: 300px; vertical-align: top;">
      Column 1 Content
    </div>
    <!--[if mso]>
    </td>
    <td width="300" valign="top">
    <![endif]-->
    <div style="display: inline-block; width: 100%; max-width: 300px; vertical-align: top;">
      Column 2 Content
    </div>
    <!--[if mso]>
    </td>
    <![endif]-->
  </tr>
</table>
```

### 🎨 Progressive Enhancement Strategy

1. **Base Layer (Tables)**: Works in 100% of email clients
2. **Enhancement Layer (CSS)**: Improves experience in modern clients
3. **Fallback Layer (Outlook VML)**: Ensures Outlook compatibility

```html
<!-- Progressive Enhancement Example -->
<table cellspacing="0" cellpadding="0" border="0">
  <tr>
    <td background="image.jpg" bgcolor="#f0f0f0" style="background-image: url('image.jpg'); background-size: cover;">
      <!--[if gte mso 9]>
      <v:rect xmlns:v="urn:schemas-microsoft-com:vml" fill="true" stroke="false" style="width: 600px; height: 300px;">
        <v:fill type="tile" src="image.jpg" color="#f0f0f0" />
        <v:textbox inset="0,0,0,0">
      <![endif]-->
      <div style="padding: 40px;">
        Content with background image
      </div>
      <!--[if gte mso 9]>
        </v:textbox>
      </v:rect>
      <![endif]-->
    </td>
  </tr>
</table>
```

---

## Email Client Best Practices

### 🔧 Technical Requirements

#### CSS Support Strategy
```css
/* ✅ Widely Supported */
background-color, color, font-family, font-size, font-weight
text-align, padding, margin, border, width, height

/* ⚠️ Limited Support - Use with Fallbacks */
background-image, border-radius, box-shadow, text-shadow

/* ❌ Avoid in Email */
position, float, flexbox, grid, transform, transition
```

#### HTML Structure Rules
```html
<!-- ✅ DO: Use tables for layout -->
<table role="presentation" cellspacing="0" cellpadding="0" border="0">

<!-- ✅ DO: Inline critical styles -->
<td style="padding: 20px; background-color: #f0f0f0;">

<!-- ✅ DO: Provide fallbacks -->
<td bgcolor="#f0f0f0" style="background-color: #f0f0f0;">

<!-- ❌ DON'T: Use div-based layouts -->
<div style="display: flex;">

<!-- ❌ DON'T: Rely on external stylesheets -->
<link rel="stylesheet" href="styles.css">
```

### 📱 Mobile Optimization
```html
<!-- Responsive meta tag -->
<meta name="viewport" content="width=device-width, initial-scale=1.0">

<!-- Mobile-friendly table widths -->
<table width="100%" style="max-width: 600px;" cellspacing="0" cellpadding="0">

<!-- Stack columns on mobile -->
<div style="display: inline-block; width: 100%; max-width: 300px; min-width: 240px;">
```

### 🧪 Testing Requirements

#### Cross-Client Testing Matrix
- **Desktop**: Outlook 2016/2019/365, Apple Mail, Thunderbird
- **Webmail**: Gmail, Outlook.com, Yahoo Mail, AOL
- **Mobile**: iPhone Mail, Gmail Mobile, Samsung Email, Outlook Mobile

#### Validation Checklist
- [ ] Tables render correctly in all clients
- [ ] Images display with proper fallbacks
- [ ] Text remains readable without custom fonts
- [ ] Responsive behavior works on mobile
- [ ] Background images work (with VML fallbacks)
- [ ] Links function properly across clients
- [ ] No broken layouts in Outlook
- [ ] Content remains accessible without images

---

## Implementation Roadmap

### Phase 1: Foundation (Week 1)
- [ ] Create UniversalElementWrapper component
- [ ] Implement capability system architecture
- [ ] Design email template generation pipeline
- [ ] Update ElementRenderer to use wrapper

### Phase 2: Element Migration (Week 2)
- [ ] Migrate TextElement to new architecture
- [ ] Migrate ButtonElement to new architecture
- [ ] Migrate ImageElement to new architecture
- [ ] Add capability-based property panels

### Phase 3: Email Generation (Week 3)
- [ ] Implement table-based HTML generator
- [ ] Add Outlook VML fallback system
- [ ] Create responsive email patterns
- [ ] Build email preview system

### Phase 4: Advanced Features (Week 4)
- [ ] Migrate SectionElement (complex container)
- [ ] Add resizable element capabilities
- [ ] Implement background image system
- [ ] Add email client testing tools

### Phase 5: Testing & Optimization (Week 5)
- [ ] Cross-client testing suite
- [ ] Performance optimization
- [ ] Documentation and examples
- [ ] Migration guide for existing elements

---

## Technical Specifications

### File Structure
```
src/
├── components/
│   ├── Builder/
│   │   ├── Elements/
│   │   │   ├── UniversalElementWrapper.jsx     # NEW
│   │   │   ├── SpacingLayer.jsx               # NEW
│   │   │   ├── SelectionRing.jsx              # NEW
│   │   │   ├── ElementRenderer.jsx            # UPDATED
│   │   │   └── elements/
│   │   │       ├── TextElement.jsx            # SIMPLIFIED
│   │   │       ├── ButtonElement.jsx          # SIMPLIFIED
│   │   │       └── SectionElement.jsx         # UPDATED
│   │   └── EmailGeneration/
│   │       ├── EmailTemplateGenerator.jsx     # NEW
│   │       ├── TableLayoutEngine.jsx          # NEW
│   │       └── EmailClientPatterns.jsx        # NEW
│   └── capabilities/
│       ├── elementCapabilities.js             # NEW
│       ├── responsiveCapabilities.js          # NEW
│       └── emailClientSupport.js              # NEW
```

### API Design
```javascript
// Element capability registration
registerElementCapability('text', {
  resizable: { horizontal: true },
  background: { color: true },
  typography: { font: true, size: true, color: true }
});

// Email generation API
const emailHTML = generateEmailHTML(builderState, {
  template: 'responsive-table',
  clientSupport: 'maximum',
  includeVMLFallbacks: true
});

// Capability-based property rendering
const PropertyPanel = ({ element }) => {
  const capabilities = getElementCapabilities(element.type);
  
  return (
    <div>
      {capabilities.resizable && <ResizeControls />}
      {capabilities.background?.color && <ColorPicker />}
      {capabilities.typography && <FontControls />}
    </div>
  );
};
```

---

## Success Metrics

### Technical Metrics
- **Code Reduction**: 60% reduction in element component code
- **Consistency**: 100% consistent selection behavior across elements
- **Email Compatibility**: 100% rendering success across target email clients
- **Performance**: <100ms email HTML generation time

### User Experience Metrics
- **Feature Parity**: All existing builder features maintained
- **New Capabilities**: Element-specific features (resize, backgrounds) enabled
- **Email Quality**: Professional email output across all clients
- **Development Speed**: 50% faster new element development

### Quality Metrics
- **Cross-Client Testing**: Pass rate >95% across all target clients
- **Accessibility**: WCAG 2.1 AA compliance in generated emails
- **Performance**: Email file size <100KB for typical campaigns
- **Maintainability**: Single source of truth for element behaviors

---

## Risk Mitigation

### Technical Risks
- **Complexity**: Gradual migration approach with feature flags
- **Performance**: Lazy loading and code splitting for large builders
- **Compatibility**: Comprehensive testing matrix and fallback systems

### Business Risks
- **User Disruption**: Backward compatibility maintained during transition
- **Timeline**: Phased rollout allows for course correction
- **Quality**: Extensive testing before each phase deployment

---

## Conclusion

This architecture redesign addresses critical needs for both developer experience and email client compatibility. By implementing a universal wrapper system with capability-based features, we achieve:

1. **Cleaner Code**: Elimination of duplication and consistent behavior
2. **Flexible System**: Easy addition of new element types and capabilities  
3. **Email Excellence**: Professional output that works across all email clients
4. **Future-Proof**: Scalable architecture for advanced features

The separation between builder components (modern React) and email templates (compatible HTML tables) ensures we can provide the best of both worlds: a great builder experience and reliable email delivery.

**Next Steps**: Review this PRD with the team, gather feedback, and begin Phase 1 implementation.