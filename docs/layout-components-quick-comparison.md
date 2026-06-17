---
title: "Layout components — quick comparison"
---

[← Back to Table of Contents](/docs/index)


| Need | Component / pattern |
|------|-------------------|
| Shared box, multiple flat rows | `ItemCardGroup` |
| Separate card per row, gaps | `ItemCard` + `ItemCardStack` |
| Form fields inside a card surface | `ItemCard` + `item-card--form-panel` |
| Two-column form cards | `ItemCardStack` + `fff-form-layout--grid` + `columns(2)` |
| Tabbed form without Filament `Tabs` chrome | `SegmentTabs` + `SegmentTab::schema()` |
| Hero / banner at top of form | `CoverCard` + `fullWidth()` + wide `ratio()` |
| Locale tabs for translatable fields | `TranslatableFields` |
| Row with switch/select | `ItemCard::schema([...])` inside group |
| Whole row navigates | `-&gt;chevron()-&gt;url()` or `-&gt;pressableAction()` |
| Settings list with header above box | `ItemCardGroup::headerStyle('outside')` |

---
