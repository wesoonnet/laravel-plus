<?php

namespace WeSoonNet\LaravelPlus\Controllers;

use Illuminate\Contracts\Auth\Authenticatable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Foundation\Bus\DispatchesJobs;
use Illuminate\Foundation\Validation\ValidatesRequests;
use Illuminate\Http\JsonResponse;
use Illuminate\Routing\Controller as BaseController;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Validator;
use Throwable;

class RootController extends BaseController
{
    use AuthorizesRequests, DispatchesJobs, ValidatesRequests;

    private array $_query_params    = [];       // 查询参数
    private array $_required_search = [];       // 搜索的必要字段
    private array $_only_filters    = [];       // 只允许获取的字段
    private array $_exclude_filters = [];       // 不允许获取的字段
    private int   $_max_limit       = 50;       // 最大获取记录数
    private array $_orderByArray    = [];       // 排序预存
    private array $_searchArray     = [];       // 搜索预存

    /**
     * 返回成功消息
     *
     * @param          $message
     * @param string   $code
     *
     * @return JsonResponse
     */
    protected function success($message = '', $code = '')
    {
        $response = [
            'success' => true,
            'message' => $message,
            'code'    => $code,
        ];

        if (config('app.debug'))
        {
            $response['debug']['sql'] = config('__debug_sql__', []);
        }

        return Response()->json($response);
    }

    /**
     * 返回失败消息
     *
     * @param string $message
     * @param string $code
     * @param int    $status
     *
     * @return JsonResponse
     */
    protected function failure($message = '', $code = '', $status = 200)
    {
        return Response()->json([
            'success' => false,
            'message' => $message,
            'code'    => $code,
        ], $status);
    }

    /**
     * Exception error
     *
     * @param Throwable $e
     *
     * @return JsonResponse
     */
    protected function error(Throwable $e)
    {
        report($e);

        return $this->failure(config('app.debug') ? $e->getMessage() : 'Server Error', 500, 500);
    }

    /**
     * 自动返回服务消息
     *
     * @param $response_obj
     *
     * @return JsonResponse
     */
    protected function done($response_obj)
    {
        return Response()->json([
            'success' => $response_obj->success,
            'message' => $response_obj->message,
            'code'    => $response_obj->code,
        ]);
    }

    /**
     * 返回 JSON
     *
     * @param $message
     *
     * @return JsonResponse
     */
    protected function json($message)
    {
        return Response()->json($message);
    }

    /**
     * 验证表单
     *
     * @param $data
     * @param $rules
     * @param $messages
     * @param $attributes
     *
     * @return bool|string
     */
    protected function validator($data, $rules, $messages, $attributes = [])
    {
        $validator = Validator::make($data, $rules, $messages, $attributes);

        if ($validator->fails())
        {
            return $validator->errors()->first();
        }

        return true;
    }

    /**
     * 当前登录用户ID
     *
     * @param null $guard
     *
     * @return null
     */
    protected function id($guard = null)
    {
        return ($user = $guard ? Auth::guard($guard)->user() : Auth::user()) ? $user->id : null;
    }

    /**
     * 当前登录用户
     *
     * @return Authenticatable
     */
    protected function user()
    {
        return Auth::user();
    }

    /**
     * 插入查询参数
     *
     * @param string $type
     * @param array  $query
     */
    protected function pushQuery(string $type, array $query)
    {
        $this->_query_params[$type] = $query;
    }

    /**
     * 限定查询字段
     *
     * @param array $fields
     */
    protected function setOnlyFilter(array $fields)
    {
        $this->_only_filters = $fields;
    }

    /**
     * 排除查询字段
     *
     * @param array $fields
     */
    protected function setExcludeFilter(array $fields)
    {
        $this->_exclude_filters = $fields;
    }

    /**
     * 设置必填搜索字段
     *
     * @param string $field
     *
     * @return void
     */
    protected function setRequiredSearch(string $field): void
    {
        $this->_required_search[] = $field;
    }

    /**
     * 设置最大返回数量
     *
     * @param int $limit
     *
     * @return void
     */
    protected function setLimit(int $limit): void
    {
        $this->_limit = $limit;
    }

    /**
     * 解析URL查询参数
     *
     * @param array         $input
     * @param Model         $model
     * @param callable|null $cb
     * @param bool          $return_json
     *
     * @return array|Builder[]|Collection|JsonResponse
     * @throws \Exception
     */
    protected function page(array $input, Model $model, callable $cb = null, bool $return_json = true)
    {
        // 解析查询参数
        $model = $this->parseQuery($input, $model, $cb);

        // 解析分页
        $_limit = (int)($input['limit'] ?? 0);

        if (0 == $_limit)
        {
            // 限制返回数据量
            $result = $model->limit($this->_max_limit)->get();

            return $return_json ? $this->success($result) : $result;
        }

        $_limit   = min($_limit, $this->_max_limit);
        $paginate = $model->paginate($_limit);
        $result   = [
            'total'        => $paginate->total() ?: 0,
            'current_page' => $paginate->currentPage(),
            'last_page'    => $paginate->lastPage(),
            'data'         => $paginate->items(),
        ];

        // 如果需要排除查询
        if (!empty($this->_exclude_filters))
        {
            $result['data'] = array_map(fn($row) => collect($row)->except($this->_exclude_filters), $result['data']);
        }

        return $return_json ? $this->success($result) : $result;
    }

    /**
     * 解析URL查询参数
     *
     * @param array         $input 请求对象
     * @param Model         $model 数据模型
     * @param callable|null $cb    回调处理
     *
     * @return Builder|Model
     * @throws \Exception
     */
    protected function parseQuery(array $input, Model $model, callable $cb = null)
    {
        //////////////////////////////////////////////////////////////
        // 解析排序
        //////////////////////////////////////////////////////////////
        if (isset($input['orderby']) && !empty($input['orderby']))
        {
            $model = $this->parseOrder($model, $input['orderby']);
        }

        //////////////////////////////////////////////////////////////
        // 解析搜索
        //////////////////////////////////////////////////////////////
        if (isset($input['search']) && !empty($input['search']))
        {
            $model = $this->parseSearch($model, $input['search']);
        }

        //////////////////////////////////////////////////////////////
        // 解析过滤
        //////////////////////////////////////////////////////////////
        if (!empty($this->_only_filters) && empty($input['filter']))
        {
            throw new \InvalidArgumentException("缺少查询过滤参数");
        }
        print_r($input['filter']);
        if (isset($input['filter']) && !empty($input['filter']))
        {
            $model = $this->parseFilter($model, $input['filter']);
        }

        //////////////////////////////////////////////////////////////
        // 解析包含关系
        //////////////////////////////////////////////////////////////
        if (isset($input['include']) && !empty($input['include']))
        {
            $model = $this->parseInclude($model, $input['include']);
        }

        //////////////////////////////////////////////////////////////
        // 解析获取数量
        //////////////////////////////////////////////////////////////
        if ($_take = ($input['take'] ?? null))
        {
            if (is_numeric($_take))
            {
                $model = $model->take($_take);
            }
        }

        //////////////////////////////////////////////////////////////
        // 解析地理距离
        //////////////////////////////////////////////////////////////
        if ($_distance = ($input['distance'] ?? null))
        {
            $distance = explode(';', $_distance);
            if (2 === count($distance) && !empty($distance[0]) && !empty($distance[1]))
            {
                $model = $model->distance($distance[1], $distance[0]);
            }
        }

        //////////////////////////////////////////////////////////////
        // 解析地理范围
        //////////////////////////////////////////////////////////////
        if ($_geofence = ($input['geofence'] ?? null))
        {
            $geofence = explode(';', $_geofence);
            if (4 === count($geofence))
            {
                $model = $model->geofence($geofence[1], $geofence[0], $geofence[2], $geofence[3]);
            }
        }

        //////////////////////////////////////////////////////////////
        // 其他辅助查询
        //////////////////////////////////////////////////////////////
        if ($_extand = ($input['extend'] ?? null))
        {
            $_extands = explode(';', $_extand);
            foreach ($_extands as $extand)
            {
                // 随机查询
                if ('random' === $extand)
                {
                    $model = $model->inRandomOrder();
                }
            }
        }

        // 回调
        if ($cb)
        {
            if ($model instanceof Builder)
            {
                $model = $cb($model);
            }
            else
            {
                $model = $cb($model->query());
            }
        }

        return $model;
    }

    /**
     * 解析排序
     *
     * @param Model|Builder $model
     * @param string        $_order
     *
     * @return Model
     */
    protected function parseOrder(Model|Builder $model, string|null $_order = '')
    {
        $_order_group = explode(';', $_order);

        // 追加参数
        if (isset($this->_query_params['orderby']))
        {
            foreach ($this->_query_params['orderby'] as $_item)
            {
                $_order_group[] = $_item;
            }
        }
        foreach ($_order_group as $_group)
        {
            if ($_group)
            {
                if (strpos($_group, ':'))
                {
                    [$_field, $_sort] = explode(':', $_group);
                    $_sort = (in_array(strtolower($_sort), ['asc', 'ascend'])) ? 'asc' : 'desc';
                    if (strpos($_field, '.'))
                    {
                        [$__with, $__field] = explode('.', $_field);
                        $this->_orderByArray[$__with][] = [$__field, $_sort];
                    }
                    else
                    {
                        $model = $model->orderBy($_field, $_sort);
                    }
                }
            }
        }

        return $model;
    }

    /**
     * 解析搜索
     *
     * @param Model|Builder $model
     * @param string        $_search
     *
     * @return void
     */
    protected function parseSearch(Model|Builder $model, string|null $_search = '')
    {
        $_search_group = explode(';', $_search);

        // 追加参数
        if (isset($this->_query_params['search']))
        {
            foreach ($this->_query_params['search'] as $_item)
            {
                $_search_group[] = $_item;
            }
        }

        // 最后生成的查询条件
        $_search_data = [];

        foreach ($_search_group as $_group)
        {
            if ($_group)
            {
                $count = substr_count($_group, ':');

                // 自定义逻辑
                $is_or = false;

                if (3 === $count)
                {
                    [$_field, $_value, $_rule, $_logic] = explode(':', $_group);
                    if ('or' === $_logic)
                    {
                        $is_or = true;
                    }
                }
                else if (2 === $count)
                {
                    [$_field, $_value, $_rule] = explode(':', $_group);
                }
                else if (1 === $count)
                {
                    [$_field, $_value] = explode(':', $_group);
                    $_rule = '=';
                }
                else
                {
                    continue;
                }

                // 空值处理
                $_value = in_array($_value, ['null', 'NULL']) ? null : $_value;
                $_value = urldecode($_value);

                // 子查询
                $with_obj    = null;
                $_with_field = null;

                if (strpos($_field, '.'))
                {
                    $_with_field_array = explode('.', $_field);
                    $_with_field       = array_pop($_with_field_array);
                    $with_obj          = implode('.', $_with_field_array);
                }

                // 条件处理
                switch ($_rule)
                {
                    case '=':
                        if ($_value != '')
                        {
                            if ($with_obj)
                            {
                                $this->_searchArray[$with_obj][] = ['=', $_with_field, $_value];
                            }
                            else
                            {
                                $model = $is_or ? $model->orWhere($_field, $_value) : $model->where($_field, $_value);
                            }
                        }
                        break;

                    case 'like':
                        $_value = (!str_contains($_value, '%')) ? "%{$_value}%" : $_value;
                        if ($with_obj)
                        {
                            $this->_searchArray[$with_obj][] = ['like', $_with_field, $_value];
                        }
                        else
                        {
                            $model = $is_or ? $model->orWhere($_field, 'like', $_value) : $model->where($_field, 'like', $_value);
                        }
                        break;

                    case 'in':
                        if (!is_array($_value))
                        {
                            $_value = (!str_contains($_value, ',')) ? [$_value] : explode(',', $_value);
                        }
                        if ($with_obj)
                        {
                            $this->_searchArray[$with_obj][] = ['in', $_with_field, $_value];
                        }
                        else
                        {
                            $model = $is_or ? $model->orWhereIn($_field, $_value) : $model->whereIn($_field, $_value);
                        }
                        break;

                    case 'has':
                        if ($_value)
                        {
                            $model = $model->whereHas($with_obj, function ($query) use ($_with_field, $_value)
                            {
                                $query->where($_with_field, $_value);
                            });
                        }
                        else
                        {
                            $model = $model->whereHas($_field);
                        }
                        break;

                    case 'has_like':
                        if ($_value)
                        {
                            $model = $model->whereHas($with_obj, function ($query) use ($_with_field, $_value)
                            {
                                $_value = (!str_contains($_value, '%')) ? "%{$_value}%" : $_value;
                                $query->where($_with_field, 'like', "%{$_value}%");
                            });
                        }
                        break;

                    case 'has_in':
                        if ($_value)
                        {
                            $model = $model->whereHas($with_obj, function ($query) use ($_with_field, $_value)
                            {
                                $query->whereIn($_with_field, explode(",", $_value));
                            });
                        }
                        break;

                    case 'notnull':
                        if ($with_obj)
                        {
                            $this->_searchArray[$with_obj][] = ['notnull', $_with_field, $_value];
                        }
                        else
                        {
                            $model = $model->whereNotNull($_field);
                        }
                        break;

                    case 'isnull':
                        if ($with_obj)
                        {
                            $this->_searchArray[$with_obj][] = ['isnull', $_with_field, $_value];
                        }
                        else
                        {
                            $model = $model->whereNull($_field);
                        }
                        break;

                    case 'between':
                        if ($with_obj)
                        {
                            $this->_searchArray[$with_obj][] = ['between', $_with_field, $_value];
                        }
                        else
                        {
                            $_value = urldecode($_value);
                            $_value = explode(',', $_value);

                            if (2 === count($_value))
                            {
                                $model = $model->whereBetween($_field, $_value);
                            }
                        }
                        break;

                    case 'between_datetime':
                        if ($with_obj)
                        {
                            $this->_searchArray[$with_obj][] = ['between', $_with_field, $_value];
                        }
                        else
                        {
                            $_value = urldecode($_value);
                            $_value = explode(',', $_value);

                            if (2 === count($_value))
                            {
                                $_value[0] = date('Y-m-d H:i:s', $_value[0]);
                                $_value[1] = date('Y-m-d H:i:s', $_value[1]);
                                $model     = $model->whereBetween($_field, $_value);
                            }
                        }
                        break;

                    default:
                        if ($_value != '' && in_array($_rule, ['>', '<', '>=', '<=', '!=']))
                        {
                            if ($with_obj)
                            {
                                $this->_searchArray[$with_obj][] = [$_rule, $_with_field, $_value];
                            }
                            else
                            {
                                $model = $is_or ? $model->orWhere($_field, $_rule, $_value) : $model->where($_field, $_rule, $_value);
                            }
                        }
                }

                $_search_data[$_field] = $_value;
            }
        }

        // 如果查询条件不存在必填选项则报错
        foreach ($this->_required_search as $f)
        {
            if (!array_key_exists($f, $_search_data) || strlen($_search_data[$f]) == 0)
            {
                throw new \InvalidArgumentException("缺少 $f 参数");
            }
        }

        return $model;
    }

    /**
     * 解析过滤
     *
     * @param Model|Builder $model
     * @param string        $_filter
     *
     * @return Builder|Model|null
     * @throws \Exception
     */
    protected function parseFilter(Model|Builder $model, string|null $_filter = '')
    {
        $_filter_group = explode(';', $_filter);
        // 追加参数
        if (isset($this->_query_params['filter']))
        {
            foreach ($this->_query_params['filter'] as $_item)
            {
                $_filter_group[] = $_item;
            }
        }

        $_filter_group = array_filter($_filter_group);

        if (!empty($_filter_group))
        {
            if (!empty($this->_only_filters))
            {
                // 非法字段
                $only_filter = array_diff($_filter_group, $this->_only_filters);

                if (!empty($only_filter))
                {
                    $only_fields = implode(',', $only_filter);

                    throw new \InvalidArgumentException("查询过滤参数(${$only_fields})无效");
                }
            }

            $model = $model->addSelect($_filter_group);
        }

        return $model;
    }

    /**
     * 解析包含
     *
     * @param Model|Builder $model
     * @param string        $_include
     *
     * @return Builder|mixed
     */
    protected function parseInclude(Model|Builder $model, string|null $_include = '')
    {
        $_include_group = explode(';', $_include);
        // 追加参数
        if (isset($this->_query_params['include']))
        {
            foreach ($this->_query_params['include'] as $_item)
            {
                $_include_group[] = $_item;
            }
        }
        sort($_include_group);
        foreach ($_include_group as $_group)
        {
            if ($_group)
            {
                // 有字段过滤
                if (strpos($_group, ':'))
                {
                    $_group_array = explode(':', $_group);
                    $_obj         = $_group_array[0];
                    $_fields      = $_group_array[1];
                    $_method      = 'with' . ucfirst($_group_array[2] ?? '');
                    $model        = call_user_func([$model, $_method], [
                        $_obj => function ($query) use ($_fields, $_obj, $_method)
                        {
                            $model = $query;

                            if ('with' == $_method)
                            {
                                // 字段过滤
                                $_fields_array  = [];
                                $_fields_array_ = explode(',', $_fields);

                                foreach ($_fields_array_ as $_field)
                                {
                                    if ($_field)
                                    {
                                        $_fields_array[] = $_field;
                                    }
                                }
                                $model = $query->select($_fields_array);

                                // 排序
                                if (in_array($_obj, array_keys($this->_orderByArray)))
                                {
                                    foreach ($this->_orderByArray[$_obj] as $_orderby)
                                    {
                                        [$k, $v] = $_orderby;
                                        $model = $query->orderBy($k, $v);
                                    }
                                }
                            }

                            if (in_array($_obj, array_keys($this->_searchArray)))
                            {
                                foreach ($this->_searchArray[$_obj] as $_search)
                                {
                                    [$_r, $_f, $_v] = $_search;

                                    switch ($_r)
                                    {
                                        case '=':
                                            $model = $model->where($_f, $_v);
                                            break;

                                        case 'like':
                                            $_v    = (false === strpos($_v, '%')) ? "%{$_v}%" : $_v;
                                            $model = $model->where($_f, 'like', $_v);
                                            break;

                                        case 'in':
                                            if (!is_array($_v))
                                            {
                                                $_v = (false === strpos($_v, ',')) ? [$_v] : explode(',', $_v);
                                            }
                                            $model = $model->whereIn($_f, $_v);
                                            break;

                                        case 'notnull':
                                            $model = $model->whereNotNull($_f);
                                            break;

                                        case 'isnull':
                                            $model = $model->whereNull($_f);
                                            break;

                                        case 'between':

                                            $_v = urldecode($_v);
                                            $_v = explode(',', $_v);

                                            if (2 === count($_v))
                                            {
                                                $model = $model->whereBetween($_f, $_v);
                                            }
                                            break;

                                        case 'between_datetime':

                                            $_v = urldecode($_v);
                                            $_v = explode(',', $_v);

                                            if (2 === count($_v))
                                            {
                                                $_v[0] = date('Y-m-d H:i:s', $_v[0]);
                                                $_v[1] = date('Y-m-d H:i:s', $_v[1]);
                                                $model = $model->whereBetween($_f, $_v);
                                            }
                                            break;
                                    }
                                }
                            }
                        },
                    ]);
                }
                else
                {
                    if (in_array($_group, array_keys($this->_searchArray)))
                    {
                        $_search = $this->_searchArray[$_group];
                        $model   = $model->with([
                            $_group => function ($query) use ($_search)
                            {
                                foreach ($_search as $_s)
                                {
                                    [$_r, $_f, $_v] = $_s;

                                    switch ($_r)
                                    {
                                        case '=':
                                            $query->where($_f, $_v);
                                            break;

                                        case 'like':
                                            $query->where($_f, 'like', $_v);
                                            break;

                                        case 'in':
                                            $query->whereIn($_f, $_v);
                                            break;

                                        case 'notnull':
                                            $query->whereNotNull($_f);
                                            break;

                                        case 'isnull':
                                            $query->whereNull($_f);
                                            break;

                                        case 'between':
                                            $_v = urldecode($_v);
                                            $_v = explode(',', $_v);

                                            if (2 === count($_v))
                                            {
                                                $query->whereBetween($_f, $_v);
                                            }
                                            break;

                                        case 'between_datetime':
                                            $_v = urldecode($_v);
                                            $_v = explode(',', $_v);

                                            if (2 === count($_v))
                                            {
                                                $_v[0] = date('Y-m-d H:i:s', $_v[0]);
                                                $_v[1] = date('Y-m-d H:i:s', $_v[1]);
                                                $query->whereBetween($_f, $_v);
                                            }
                                            break;
                                    }
                                }
                            },
                        ]);
                    }
                    else
                    {
                        $model = $model->with($_group);
                        if (in_array($_group, array_keys($this->_orderByArray)))
                        {
                            foreach ($this->_orderByArray[$_group] as $_orderby)
                            {
                                [$k, $v] = $_orderby;
                                $model = $model->orderBy($k, $v);
                            }
                        }
                    }
                }
            }
        }

        return $model;
    }
}
