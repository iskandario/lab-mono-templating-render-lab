ALTER TABLE benchmark_runs
    DROP CONSTRAINT IF EXISTS benchmark_runs_template_id_fkey;

ALTER TABLE benchmark_runs
    ALTER COLUMN template_id DROP NOT NULL;

ALTER TABLE benchmark_runs
    ADD COLUMN IF NOT EXISTS template_body_snapshot TEXT NOT NULL DEFAULT '';

UPDATE benchmark_runs AS b
SET template_body_snapshot = t.template_body
FROM templates AS t
WHERE b.template_id = t.template_id
  AND b.template_body_snapshot = '';
