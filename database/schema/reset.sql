DO $fix$
DECLARE
  target_schema text := 'public';

  rec RECORD;
  v_next bigint;
  v_seq  text;
  v_is_identity boolean;
BEGIN
  RAISE NOTICE 'Fixing PK auto-increment in schema: %', target_schema;

  -- All single-column int/bigint PKs in the target schema
  FOR rec IN
    SELECT
      n.nspname  AS table_schema,
      c.relname  AS table_name,
      a.attname  AS column_name,
      format('%I.%I', n.nspname, c.relname) AS fqtn,
      (ic.is_identity = 'YES') AS is_identity,
      pg_get_serial_sequence(format('%I.%I', n.nspname, c.relname), a.attname) AS seq_name
    FROM pg_class c
    JOIN pg_namespace n         ON n.oid = c.relnamespace
    JOIN pg_index i             ON i.indrelid = c.oid AND i.indisprimary
    JOIN pg_attribute a         ON a.attrelid = c.oid AND a.attnum = ANY(i.indkey)
    JOIN information_schema.columns ic
         ON ic.table_schema = n.nspname
        AND ic.table_name  = c.relname
        AND ic.column_name = a.attname
    WHERE c.relkind IN ('r','p')                          -- tables & partitioned tables
      AND n.nspname = target_schema
      AND (a.atttypid = 'int4'::regtype OR a.atttypid = 'int8'::regtype)
      AND i.indnatts = 1                                  -- single-column PK
  LOOP
    -- Next value to hand out
    EXECUTE format('SELECT COALESCE(MAX(%I),0)+1 FROM %s', rec.column_name, rec.fqtn) INTO v_next;

    v_is_identity := rec.is_identity;
    v_seq := rec.seq_name;

    IF v_is_identity THEN
      -- IDENTITY: set next directly
      EXECUTE format('ALTER TABLE %s ALTER COLUMN %I RESTART WITH %s', rec.fqtn, rec.column_name, v_next);
      RAISE NOTICE 'IDENTITY restarted: %.% (col %) -> next %',
        rec.table_schema, rec.table_name, rec.column_name, v_next;

    ELSIF v_seq IS NOT NULL THEN
      -- Sequence-backed
      IF v_next = 1 THEN
        -- Empty table: make nextval() return 1 without violating MINVALUE 1
        EXECUTE format('SELECT setval(%L, 1, false)', v_seq);
        RAISE NOTICE 'Sequence aligned (empty table): % -> next 1', v_seq;
      ELSE
        EXECUTE format('SELECT setval(%L, %s - 1, true)', v_seq, v_next);
        RAISE NOTICE 'Sequence aligned: % uses % (next -> %)', rec.fqtn, v_seq, v_next;
      END IF;

    ELSE
      -- Create and attach a sequence safely
      v_seq := format('%I.%I_%I_seq', rec.table_schema, rec.table_name, rec.column_name);

      EXECUTE format('CREATE SEQUENCE IF NOT EXISTS %s', v_seq);

      EXECUTE format(
        'ALTER TABLE %s ALTER COLUMN %I SET DEFAULT nextval(%L)',
        rec.fqtn, rec.column_name, v_seq
      );

      EXECUTE format(
        'ALTER SEQUENCE %s OWNED BY %s.%I',
        v_seq, rec.fqtn, rec.column_name
      );

      IF v_next = 1 THEN
        EXECUTE format('SELECT setval(%L, 1, false)', v_seq);
        RAISE NOTICE 'Sequence created & aligned (empty table): % -> next 1', v_seq;
      ELSE
        EXECUTE format('SELECT setval(%L, %s - 1, true)', v_seq, v_next);
        RAISE NOTICE 'Sequence created & aligned: % -> % (next -> %)', v_seq, rec.fqtn, v_next;
      END IF;
    END IF;
  END LOOP;

  RAISE NOTICE 'Done.';
END
$fix$;
