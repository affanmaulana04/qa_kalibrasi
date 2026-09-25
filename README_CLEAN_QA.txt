QA Calibration Clean Package

- HRD user-management flags/tables were removed from the user-management pages.
- QA roles: Admin, Pembuat, Pemeriksa, Penyetuju.
- User passwords follow existing legacy MD5 login behavior.
- Run sql/qa_clean_migration.sql before testing validator roles.
- Check/Update QA creates qa_pengecekan with status_validasi=menunggu_pemeriksa.
- History printing now reads validator audit from qa_validasi, so the report signature checkmarks reflect the latest validation state.
