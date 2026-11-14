CREATE TABLE coffees (
    id UUID PRIMARY KEY,
    name TEXT NOT NULL CHECK (name ~ '[^[:space:]]'),
    origin TEXT NOT NULL CHECK (origin ~ '[^[:space:]]'),
    roast_level TEXT NOT NULL CHECK (roast_level IN ('light', 'medium', 'dark')),
    created_at TIMESTAMPTZ NOT NULL
);
