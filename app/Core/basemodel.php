<?php

abstract class BaseModel
{
    protected ?int $id = null;
    protected $created_at;
    protected $updated_at;

    /* ======================
       GETTER
    ====================== */
    public function getId(): ?int
    {
        return $this->id;
    }

    public function getCreatedAt(): ?string
    {
        return $this->created_at;
    }

    public function getUpdatedAt(): ?string
    {
        return $this->updated_at;
    }

    /* ======================
       SETTER (INI YANG HILANG)
    ====================== */
    public function setId(int $id): void
    {
        if ($id <= 0) {
            throw new InvalidArgumentException("ID tidak valid");
        }
        $this->id = $id;
    }

    public function setCreatedAt(string $created_at): void
    {
        $this->created_at = $created_at;
    }

    public function setUpdatedAt(string $updated_at): void
    {
        $this->updated_at = $updated_at;
    }

    /* ======================
       KONTRAK WAJIB
    ====================== */
    abstract public function save(): bool;
    abstract public function delete(): bool;
}
