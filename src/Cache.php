<?php

namespace Ekok\Cache;

class Cache
{
    private $ref;
    private $dsn;
    private $seed;
    private $driver;

    public function __construct(string|bool $dsn = null, string $seed = null)
    {
        $this->load($dsn, $seed);
    }

    public function load(string|bool|null $dsn, string $seed = null): static
    {
        $this->dsn = true === $dsn ? 'folder=' . sys_get_temp_dir() : $dsn;
        $this->ref = null;
        $this->driver = null;
        $this->seed = $seed ?? 'cache';

        list($this->driver, $this->ref) = match(true) {
            !!preg_match('/(?:folder|directory|dir)\h*=\h*(.+)\h*/i', $this->dsn, $match) => array('folder', rtrim(strtr($match[1], '\\', '/'), '/') . '/'),
            default => array(null, null),
        };

        'folder' !== $this->driver || is_dir($this->ref) || mkdir($this->ref, 0775, true);

        return $this;
    }

    public function has(string $key, array &$data = null): bool
    {
        $ndx = $this->seed . '.' . $key;
        $raw = match($this->driver) {
            'folder' => file_exists($path = $this->ref . $ndx) ? file_get_contents($path) : null,
            default => null,
        };
        $data = array(null, 0, 0);

        if ($raw) {
            list($val, $ttl, $time) = $this->unserialize($raw);

            if ($ttl === 0 || $time + $ttl > microtime(true)) {
                $data = array($val, $ttl, $time);

                return true;
            }

            $this->remove($key);
        }

        return !$this->ref;
    }

    public function get(string $key)
    {
        return $this->has($key, $data) ? $data[0] : null;
    }

    public function set(string $key, $val, int $ttl = null, bool &$saved = null): static
    {
        if (null === $ttl && $this->has($key, $data)) {
            $ttl_ = $data[1];
        }

        $ndx = $this->seed . '.' . $key;
        $saved = match($this->driver) {
            'folder' => false !== file_put_contents($this->ref . str_replace(array('/', '\\'), '', $ndx), $this->serialize($val, $ttl_ ?? $ttl ?? 0)),
            default => true,
        };

        return $this;
    }

    public function remove(string $key, bool &$removed = null): static
    {
        $ndx = $this->seed . '.' . $key;
        $removed = match($this->driver) {
            'folder' => file_exists($path = $this->ref . $ndx) ? unlink($path) : true,
            default => true,
        };

        return $this;
    }

    public function reset(string $suffix = null, int &$count = null): static
    {
        $rule = '/' . preg_quote($this->seed . '.' , '/') . '.*' . preg_quote($suffix ?? '', '/') . '/';
        $count = match($this->driver) {
            'folder' => array_reduce(
                glob($this->ref . '*'),
                static fn (int $removed, string $file) => $removed + intval(preg_match($rule, $file) && unlink($file)),
                0,
            ),
            default => 0,
        };

        return $this;
    }

    public function isEnabled(): bool
    {
        return !!$this->driver;
    }

    public function isDisabled(): bool
    {
        return !$this->driver;
    }

    public function getDriver(): string|null
    {
        return $this->driver;
    }

    public function getRef()
    {
        return $this->ref;
    }

    public function getDsn(): string|bool|null
    {
        return $this->dsn;
    }

    public function getSeed(): string
    {
        return $this->seed;
    }

    protected function serialize($val, int $ttl): string
    {
        return serialize(array($val, $ttl, microtime(true)));
    }

    protected function unserialize(string $raw): array
    {
        return (array) unserialize($raw);
    }
}
