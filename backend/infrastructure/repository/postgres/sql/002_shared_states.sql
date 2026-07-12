CREATE TABLE IF NOT EXISTS shared_states (
    state_id TEXT PRIMARY KEY,
    owner_id TEXT NOT NULL REFERENCES users (user_id),
    state_json JSONB NOT NULL,
    created_at TIMESTAMPTZ NOT NULL
);

CREATE INDEX IF NOT EXISTS shared_states_owner_created_idx
    ON shared_states (owner_id, created_at DESC, state_id ASC);
