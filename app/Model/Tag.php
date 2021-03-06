<?php

/*
 * This file is part of the Tinyissue package.
 *
 * (c) Mohamed Alsharaf <mohamed.alsharaf@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */
namespace Tinyissue\Model;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Query;

/**
 * Tag is model class for tags
 *
 * @author Mohamed Alsharaf <mohamed.alsharaf@gmail.com>
 * @property int     $id
 * @property int     $parent_id
 * @property string  $name
 * @property string  $fullname
 * @property string  $bgcolor
 * @property boolean $group
 * @property Tag     $parent
 * @method   Query\Builder where($column, $operator = null, $value = null, $boolean = 'and')
 */
class Tag extends Model
{
    const STATUS_OPEN = 'open';
    const STATUS_CLOSED = 'closed';
    const GROUP_STATUS = 'status';
    public $timestamps = true;
    public $fillable = ['parent_id', 'name', 'bgcolor', 'group'];
    protected $table = 'tags';

    /**
     * Returns the parent/group for the tag
     *
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function parent()
    {
        return $this->belongsTo('Tinyissue\Model\Tag', 'parent_id');
    }

    /**
     * Parent tag/group have many tags
     *
     * @return \Illuminate\Database\Eloquent\Relations\HasMany
     */
    public function tags()
    {
        return $this->hasMany('Tinyissue\Model\Tag', 'parent_id');
    }

    /**
     * Returns issues for the Tag. Tag can belong to many issues & issue can have many tags
     *
     * @return \Illuminate\Database\Eloquent\Relations\BelongsToMany
     */
    public function issues()
    {
        return $this->belongsToMany('Tinyissue\Model\Project\Issue', 'projects_issues_tags', 'issue_id', 'tag_id');
    }

    /**
     * Returns collection of all groups and eager load their tags
     *
     * @return \Illuminate\Database\Eloquent\Collection
     */
    public function getGroupTags()
    {
        return $this->with('tags')->where('group', '=', true)->orderBy('group', 'DESC')->orderBy('name', 'ASC')->get();
    }

    /**
     * Returns collection of all groups
     *
     * @return \Illuminate\Database\Eloquent\Collection
     */
    public function getGroups()
    {
        return $this->where('group', '=', true)->orderBy('name', 'ASC')->get();
    }

    /**
     * Search tags by name
     *
     * @param string $term
     *
     * @return \Illuminate\Database\Eloquent\Collection|static[]
     */
    public function searchTags($term)
    {
        return $this->with('parent')->where('name', 'like', '%' . $term . '%')->where('group', '=', false)->get();
    }

    /**
     * Generate a URL for the tag
     *
     * @param string $url
     *
     * @return mixed
     */
    public function to($url)
    {
        return \URL::to('administration/tag/' . $this->id . (($url) ? '/' . $url : ''));
    }

    /**
     * Returns tag full name with prefix group name and ":" in between
     *
     * @return string
     */
    public function getFullNameAttribute()
    {
        return (isset($this->parent->name) ? ucwords($this->parent->name) : '') . ':' . ucwords($this->attributes['name']);
    }

    /**
     * Create new tag from string of group name and tag name
     *
     * @param string $tagFullName
     *
     * @return $this|boolean
     */
    public function createTagFromString($tagFullName)
    {
        list($groupName, $tagName) = explode(':', $tagFullName);

        // Check if group name is valid
        $groupTag = $this->validOrCreate($groupName);

        if (!$groupTag) {
            return false;
        }

        // Create new tag or return existing one
        return $this->validOrCreate($tagName, $groupTag);
    }

    /**
     * Create a new tag if valid or return existing one
     *
     * @param   string $name
     * @param null|Tag $parent
     *
     * @return boolean|$this
     */
    public function validOrCreate($name, $parent = null)
    {
        $group = $parent === null ? true : false;
        $tag = $this->where('name', '=', $name)->first();
        if ($tag && $tag->group != $group) {
            return false;
        }

        if (!$tag) {
            $tag = new static();
            $tag->name = $name;
            $tag->group = $group;
            if (!is_null($parent)) {
                $tag->parent_id = $parent->id;
                $tag->setRelation('parent', $parent);
            }
            $tag->save();
        }

        return $tag;
    }
}
