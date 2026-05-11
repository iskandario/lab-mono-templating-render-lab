ALTER TABLE templates
    ADD COLUMN IF NOT EXISTS is_public BOOLEAN NOT NULL DEFAULT FALSE;

CREATE INDEX IF NOT EXISTS templates_public_updated_idx
    ON templates (is_public, updated_at DESC, template_id ASC)
    WHERE is_active = TRUE;
