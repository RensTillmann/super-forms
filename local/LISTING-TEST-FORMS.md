# Listings Extension - Test Forms Reference

**Generated:** 2025-11-13
**Database:** f4d.nl/dev
**Total Entries in EAV Table:** 1,867

## Overview

This document lists forms that have the Listings extension configured along with their entry counts. These forms can be used to test the Listings extension with the new EAV data storage system.

---

## ⭐ Recommended Test Candidates

Forms with BOTH listings configured AND substantial entry data:

| Form ID | Form Name | Entry Count | Notes |
|---------|-----------|-------------|-------|
| **61768** | Test Cleanup Pre Submission | **188** | ✅ Best candidate - most entries |
| **58791** | PDF Font family | **134** | ✅ Excellent - substantial data |
| **63230** | Costume Form v2 | **52** | ✅ Good dataset |
| **48987** | Stripe iDeal | **56** | ✅ Good dataset |
| **62281** | Default flag for international phone number based on translation | **44** | ✅ Good dataset |
| **62549** | Group Roster FDF 2023 | **43** | ✅ Good dataset |
| **63512** | Costume Form v3 | **42** | ✅ Good dataset |
| **63536** | Costume Form v4 | **38** | ✅ Good dataset |
| **60322** | Form Name | **29** | ✅ Decent dataset |
| **62939** | PDF with Image | **27** | ✅ Decent dataset |
| **60015** | Jan Rosier v7 | **19** | ✅ Usable dataset |

---

## Top 20 Forms by Entry Count

All forms in the database sorted by number of entries (regardless of listings configuration):

| Rank | Form ID | Form Name | Entry Count | Has Listings? |
|------|---------|-----------|-------------|---------------|
| 1 | 64127 | Employment Contract Form – Contracts Manager (SA) | 223 | ❌ |
| 2 | 61768 | Test Cleanup Pre Submission | 188 | ✅ |
| 3 | 58791 | PDF Font family | 134 | ✅ |
| 4 | 65258 | Mitglied werden | 120 | ❌ |
| 5 | 69534 | Form Name | 114 | ❌ |
| 6 | 48987 | Stripe iDeal | 56 | ✅ |
| 7 | 63230 | Costume Form v2 | 52 | ✅ |
| 8 | 64775 | Employment Contract Form – Contracts Manager (SA) | 48 | ❌ |
| 9 | 72344 | PDF + Signature Two persons | 47 | ❌ |
| 10 | 62281 | Default flag for international phone number based on translation | 44 | ✅ |
| 11 | 70040 | Form Name | 44 | ❌ |
| 12 | 62549 | Group Roster FDF 2023 | 43 | ✅ |
| 13 | 63512 | Costume Form v3 | 42 | ✅ |
| 14 | 70605 | Stripe Subscription | 38 | ❌ |
| 15 | 63536 | Costume Form v4 | 38 | ✅ |
| 16 | 60322 | Form Name | 29 | ✅ |
| 17 | 62939 | PDF with Image | 27 | ✅ |
| 18 | 62173 | isset() statement | 25 | ✅ |
| 19 | 73561 | Testing Triggers | 23 | ❌ |
| 20 | 60015 | Jan Rosier v7 | 19 | ✅ |

---

## "Front-end Listing" Test Forms

These forms were specifically created for testing the Listings extension but have **no entries**:

| Form ID | Form Name | Entry Count |
|---------|-----------|-------------|
| 40607 | Front-end Listing | 0 |
| 40609 | Front-end Listing | 0 |
| 40611 | Front-end Listing | 0 |
| 40615 | Front-end Listing | 0 |
| 40616 | Front-end Listing | 0 |
| 40617 | Front-end Listing | 0 |
| 40618 | Front-end Listing | 0 |
| 40619 | Front-end Listing | 0 |
| 40620 | Front-end Listing | 0 |
| 40622 | Front-end Listing | 0 |
| 40624 | Front-end Listing | 0 |
| 40626 | Front-end Listing | 0 |
| 40666 | Front-end Listing | 0 |
| 40775 | Front-end Listing | 0 |
| 40776 | Front-end Listing | 0 |
| 40777 | Front-end Listing | 0 |
| 40778 | Front-end Listing | 0 |
| 40873 | Front-end Listing | 0 |
| 43254 | Front-end Listing | 0 |
| 43257 | Front-end Listing | 0 |
| 43259 | Front-end Listing | 0 |
| 43261 | Front-end Listing | 0 |
| 43263 | Front-end Listing | 0 |
| 52198 | Test Front-end Listing | 0 |
| 52288 | Front-end Listing Test | 0 |
| 52842 | Front-end listings | 0 |
| 53078 | Front-end listing test front-end | 0 |

---

## How to Test with These Forms

### 1. Create a Test Page

Create a WordPress page with the listings shortcode:

```
[super_listings id="61768"]
```

### 2. Access the Listings

Visit the page in your browser:
```
https://f4d.nl/dev/test-listings/
```

### 3. Test EAV Performance

The listings extension will query the new EAV table (`wp_superforms_entry_data`) instead of serialized post_meta data, allowing you to test:

- ✅ Search/filter performance (10-100x faster with indexes)
- ✅ Sorting by custom fields
- ✅ Pagination
- ✅ Listing display with real entry data

### 4. Verify Data Integrity

Compare listings output between:
- Forms with migrated data (EAV table)
- Forms using old serialized storage
- Ensure identical results

---

## Database Queries Used

### Count entries per form:
```sql
SELECT
    field_value as form_id,
    COUNT(DISTINCT entry_id) as entry_count
FROM wp_superforms_entry_data
WHERE field_name = 'hidden_form_id'
GROUP BY field_value
ORDER BY entry_count DESC;
```

### Find forms with listings configured:
```sql
SELECT p.ID, p.post_title
FROM wp_posts p
JOIN wp_postmeta pm ON p.ID = pm.post_id
WHERE p.post_type = 'super_form'
AND pm.meta_key = '_super_form_settings'
AND pm.meta_value LIKE '%_listings%';
```

### Check migration status:
```sql
SELECT COUNT(DISTINCT entry_id) as total_entries_in_eav
FROM wp_superforms_entry_data;
```

---

## Notes

- **Migration Status:** All 1,867 entries have been migrated to the EAV table
- **Listings Shortcode:** `[super_listings id="FORM_ID"]`
- **Admin URL:** https://f4d.nl/dev/wp-admin/
- **Forms with listings configured:** 1,000+ forms (many are test/duplicate forms)
- **Forms with both listings + substantial data:** 11 forms (recommended for testing)

---

## Next Steps

1. ✅ Choose a test form from the recommended list (Form 61768 recommended)
2. ✅ Create a test page with the listings shortcode
3. ✅ Verify listings display correctly with EAV data
4. ✅ Test search/filter functionality
5. ✅ Compare performance with old serialized storage
6. ✅ Validate data integrity between storage methods
