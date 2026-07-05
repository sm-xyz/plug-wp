<?php
/**
 * Illu Optimize — Redis Object Cache Drop-in
 * Compatible dengan WP_REDIS_HOST, WP_REDIS_PORT, WP_REDIS_PASSWORD, WP_REDIS_DATABASE, WP_CACHE_KEY_SALT.
 */

if ( ! defined( 'ABSPATH' ) ) exit;

class WP_Object_Cache {

    private ?object $redis     = null;
    private bool    $connected = false;
    private array   $local     = [];
    private string  $prefix    = '';
    public  int     $cache_hits   = 0;
    public  int     $cache_misses = 0;

    public function __construct() {
        $this->prefix = ( defined('WP_CACHE_KEY_SALT') ? WP_CACHE_KEY_SALT : '' )
            . ( defined('DB_NAME') ? DB_NAME : '' ) . ':';

        if ( defined('WP_REDIS_HOST') && extension_loaded('redis') ) {
            try {
                $r = new \Redis();
                $r->connect(
                    defined('WP_REDIS_HOST')     ? WP_REDIS_HOST     : '127.0.0.1',
                    defined('WP_REDIS_PORT')     ? WP_REDIS_PORT     : 6379,
                    2.0
                );
                if ( defined('WP_REDIS_PASSWORD') && WP_REDIS_PASSWORD ) $r->auth(WP_REDIS_PASSWORD);
                if ( defined('WP_REDIS_DATABASE') && WP_REDIS_DATABASE ) $r->select(WP_REDIS_DATABASE);
                $r->ping();
                $this->redis     = $r;
                $this->connected = true;
            } catch (\Exception $e) {
                $this->connected = false;
            }
        }
    }

    private function key( string $key, string $group ): string {
        return $this->prefix . $group . ':' . $key;
    }

    public function get( $key, string $group = 'default', bool $force = false, &$found = null ) {
        $k = $this->key( $key, $group );
        if ( ! $force && isset( $this->local[$k] ) ) {
            $found = true; $this->cache_hits++; return $this->local[$k];
        }
        if ( $this->connected ) {
            $val = $this->redis->get($k);
            if ( $val !== false ) {
                $dec = @unserialize($val);
                $this->local[$k] = $dec;
                $found = true; $this->cache_hits++;
                return $dec;
            }
        }
        $found = false; $this->cache_misses++;
        return false;
    }

    public function set( $key, $data, string $group = 'default', int $expire = 0 ): bool {
        $k = $this->key( $key, $group );
        $this->local[$k] = $data;
        if ( $this->connected ) {
            $s = serialize($data);
            return $expire > 0 ? (bool) $this->redis->setex($k,$expire,$s) : (bool) $this->redis->set($k,$s);
        }
        return true;
    }

    public function add( $key, $data, string $group = 'default', int $expire = 0 ): bool {
        if ( $this->get($key,$group) !== false ) return false;
        return $this->set($key,$data,$group,$expire);
    }

    public function replace( $key, $data, string $group = 'default', int $expire = 0 ): bool {
        if ( $this->get($key,$group) === false ) return false;
        return $this->set($key,$data,$group,$expire);
    }

    public function delete( $key, string $group = 'default' ): bool {
        $k = $this->key($key,$group);
        unset( $this->local[$k] );
        if ( $this->connected ) { $this->redis->del($k); }
        return true;
    }

    public function incr( $key, int $offset = 1, string $group = 'default' ) {
        $k = $this->key($key,$group);
        if ( $this->connected ) {
            $val = $this->redis->incrBy($k,$offset);
            $this->local[$k] = $val;
            return $val;
        }
        $val = (int)($this->local[$k] ?? 0) + $offset;
        $this->local[$k] = $val;
        return $val;
    }

    public function decr( $key, int $offset = 1, string $group = 'default' ) {
        return $this->incr($key,-$offset,$group);
    }

    public function flush(): bool {
        $this->local = [];
        if ( $this->connected ) {
            $keys = $this->redis->keys($this->prefix.'*');
            if (!empty($keys)) $this->redis->del($keys);
        }
        return true;
    }

    public function flush_group( string $group ): bool {
        $pattern = $this->prefix . $group . ':*';
        foreach ( $this->local as $k => $v ) {
            if ( strpos($k, $this->prefix.$group.':') === 0 ) unset($this->local[$k]);
        }
        if ( $this->connected ) {
            $keys = $this->redis->keys($pattern);
            if (!empty($keys)) $this->redis->del($keys);
        }
        return true;
    }

    public function close(): bool { return true; }
    public function switch_to_blog( int $blog_id ) { $this->prefix = ($blog_id === 1 ? '' : $blog_id.':').(defined('WP_CACHE_KEY_SALT')?WP_CACHE_KEY_SALT:'').(defined('DB_NAME')?DB_NAME:'').':'; }
    public function is_redis_connected(): bool { return $this->connected; }
    public function get_stats(): array { return ['hits'=>$this->cache_hits,'misses'=>$this->cache_misses,'redis'=>$this->connected]; }
}

// ── WP Object Cache API ────────────────────────────────────────────────────────
function wp_cache_init() { global $wp_object_cache; $wp_object_cache = new WP_Object_Cache(); }
function wp_cache_add($key,$data,$group='',$expire=0){global $wp_object_cache;return $wp_object_cache->add($key,$data,$group?$group:'default',$expire);}
function wp_cache_incr($key,$offset=1,$group=''){global $wp_object_cache;return $wp_object_cache->incr($key,$offset,$group?$group:'default');}
function wp_cache_decr($key,$offset=1,$group=''){global $wp_object_cache;return $wp_object_cache->decr($key,$offset,$group?$group:'default');}
function wp_cache_close(){global $wp_object_cache;return $wp_object_cache->close();}
function wp_cache_delete($key,$group=''){global $wp_object_cache;return $wp_object_cache->delete($key,$group?$group:'default');}
function wp_cache_flush(){global $wp_object_cache;return $wp_object_cache->flush();}
function wp_cache_flush_group($group){global $wp_object_cache;if(method_exists($wp_object_cache,'flush_group'))return $wp_object_cache->flush_group($group);return false;}
function wp_cache_get($key,$group='',$force=false,&$found=null){global $wp_object_cache;return $wp_object_cache->get($key,$group?$group:'default',$force,$found);}
function wp_cache_get_multiple($keys,$group='',$force=false){$res=[];foreach($keys as $k)$res[$k]=wp_cache_get($k,$group,$force);return $res;}
function wp_cache_replace($key,$data,$group='',$expire=0){global $wp_object_cache;return $wp_object_cache->replace($key,$data,$group?$group:'default',$expire);}
function wp_cache_set($key,$data='',$group='',$expire=0){global $wp_object_cache;return $wp_object_cache->set($key,$data,$group?$group:'default',$expire);}
function wp_cache_set_multiple($data,$group='',$expire=0){$res=[];foreach($data as $k=>$v)$res[$k]=wp_cache_set($k,$v,$group,$expire);return $res;}
function wp_cache_delete_multiple($keys,$group=''){$res=[];foreach($keys as $k)$res[$k]=wp_cache_delete($k,$group);return $res;}
function wp_cache_switch_to_blog($id){global $wp_object_cache;$wp_object_cache->switch_to_blog($id);}
function wp_cache_add_non_persistent_groups($groups){}
function wp_cache_add_global_groups($groups){}
