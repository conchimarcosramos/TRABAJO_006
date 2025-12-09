CREATE TABLE IF NOT EXISTS public.alumnos_cursos
(
    alumno_id integer NOT NULL,
    curso_id integer NOT NULL,
    fecha_matricula timestamp with time zone DEFAULT CURRENT_TIMESTAMP,
    CONSTRAINT alumnos_cursos_pkey PRIMARY KEY (alumno_id, curso_id),
    CONSTRAINT alumnos_cursos_alumno_id_fkey FOREIGN KEY (alumno_id)
        REFERENCES public.alumnos (id) MATCH SIMPLE
        ON UPDATE NO ACTION
        ON DELETE CASCADE,
    CONSTRAINT alumnos_cursos_curso_id_fkey FOREIGN KEY (curso_id)
        REFERENCES public.cursos (id) MATCH SIMPLE
        ON UPDATE NO ACTION
        ON DELETE CASCADE
)

TABLESPACE pg_default;

ALTER TABLE public.alumnos_cursos
    OWNER to admin;

-- Index: public.idx_alumnos_cursos_alumno_id
CREATE INDEX IF NOT EXISTS idx_alumnos_cursos_alumno_id
    ON public.alumnos_cursos USING btree
    (alumno_id ASC NULLS LAST)
    TABLESPACE pg_default;
-- Index: public.idx_alumnos_cursos_curso_id
CREATE INDEX IF NOT EXISTS idx_alumnos_cursos_curso_id
    ON public.alumnos_cursos USING btree
    (curso_id ASC NULLS LAST)
    TABLESPACE pg_default;