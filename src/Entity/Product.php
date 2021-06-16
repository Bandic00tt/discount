<?php

namespace App\Entity;

use App\Repository\ProductRepository;
use Doctrine\ORM\Mapping as ORM;

/**
 * @ORM\Entity(repositoryClass=ProductRepository::class)
 */
class Product
{
    /**
     * @ORM\Id
     * @ORM\GeneratedValue
     * @ORM\Column(type="integer")
     */
    private ?int $id;

    /**
     * @ORM\Column(type="integer")
     */
    private ?int $location_id;

    /**
     * @ORM\Column(type="integer")
     */
    private ?int $product_id;

    /**
     * @ORM\Column(type="string", length=512)
     */
    private ?string $name;

    /**
     * @ORM\Column(type="text", nullable=true)
     */
    private ?string $img_link;

    /**
     * @ORM\Column(type="boolean")
     */
    private ?bool $is_img_local;

    /**
     * @ORM\Column(type="integer")
     */
    private ?int $created_at;

    /**
     * @ORM\Column(type="integer")
     */
    private ?int $updated_at;

    public function getId(): ?int
    {
        return $this->id;
    }

    /**
     * @param mixed $id
     */
    public function setId($id): void
    {
        $this->id = $id;
    }

    public function getLocationId(): ?int
    {
        return $this->location_id;
    }

    public function setLocationId(int $location_id): self
    {
        $this->location_id = $location_id;

        return $this;
    }

    public function getProductId(): ?int
    {
        return $this->product_id;
    }

    public function setProductId(int $product_id): self
    {
        $this->product_id = $product_id;

        return $this;
    }

    public function getName(): ?string
    {
        return $this->name;
    }

    public function setName(string $name): self
    {
        $this->name = $name;

        return $this;
    }

    /** @noinspection PhpUnused */
    public function getImgLink(): ?string
    {
        return $this->img_link;
    }

    public function setImgLink(?string $img_link): self
    {
        $this->img_link = $img_link;

        return $this;
    }

    public function getIsImgLocal(): ?bool
    {
        return $this->is_img_local;
    }

    public function setIsImgLocal(bool $is_img_local): self
    {
        $this->is_img_local = $is_img_local;

        return $this;
    }

    public function getCreatedAt(): ?int
    {
        return $this->created_at;
    }

    public function setCreatedAt(int $created_at): self
    {
        $this->created_at = $created_at;

        return $this;
    }

    public function getUpdatedAt(): ?int
    {
        return $this->updated_at;
    }

    public function setUpdatedAt(int $updated_at): self
    {
        $this->updated_at = $updated_at;

        return $this;
    }

    // END OF GETTERS AND SETTERS

    /**
     * @return string
     */
    public function getImgPathLink(): string
    {
        if ($this->getIsImgLocal() === 0) {
            return $this->getImgLink();
        }

        return '/img/products/'. pathinfo($this->getImgLink())['basename'];
    }
}
