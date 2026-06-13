(function () {
    function getHostWindow() {
        try {
            if (window.parent && window.parent !== window && ((window.parent.layui && window.parent.layui.layer) || window.parent.layer)) {
                return window.parent;
            }
        } catch (e) {}
        return window;
    }

    function getHostDocument() {
        return getHostWindow().document || document;
    }

    function ensureStyles() {
        var hostDocument = getHostDocument();
        if (!hostDocument.getElementById('ew-css-cascader') && window.layui && layui.cache && layui.cache.base) {
            var cascaderLink = hostDocument.createElement('link');
            cascaderLink.id = 'ew-css-cascader';
            cascaderLink.rel = 'stylesheet';
            cascaderLink.href = layui.cache.base + 'cascader/cascader.css';
            hostDocument.head.appendChild(cascaderLink);
        }
        if (hostDocument.getElementById('product-detail-editor-style')) return;
        var style = hostDocument.createElement('style');
        style.id = 'product-detail-editor-style';
        style.textContent =
            '.layer-drawer-right{position:fixed!important;top:0!important;right:0!important;bottom:0!important;height:100vh!important;max-height:100vh!important;transform:translateX(100%)!important;border-radius:0!important;box-shadow:-2px 0 8px rgba(0,0,0,.15)!important;}' +
            '.layer-drawer-right.layer-drawer-show{transform:translateX(0)!important;transition:transform .3s ease-out!important;}' +
            '.layer-drawer-right .layui-layer-title{height:42px;line-height:42px;}' +
            '.layer-drawer-right .layui-layer-content{height:calc(100vh - 42px)!important;max-height:calc(100vh - 42px)!important;overflow-y:auto;overflow-x:hidden;}' +
            '.product-detail-drawer{padding:20px;background:#fff;}' +
            '.product-detail-tip{margin-bottom:16px;padding:10px 14px;border:1px solid #d9ecff;border-radius:6px;background:#f8fbff;color:#4b5b76;font-size:13px;}' +
            '.product-detail-tabs{display:flex;gap:10px;align-items:center;margin-bottom:16px;flex-wrap:wrap;}' +
            '.product-detail-tab-btn{padding:0 18px;height:36px;line-height:36px;border:1px solid #d9d9d9;border-radius:6px;background:#fff;color:#666;font-size:14px;cursor:pointer;transition:all .2s ease;}' +
            '.product-detail-tab-btn.active{border-color:#1890ff;background:#e6f7ff;color:#1890ff;}' +
            '.product-detail-tab-panel{display:none;min-height:520px;}.product-detail-tab-panel.active{display:block;}' +
            '.product-detail-tab-frame{width:100%;height:520px;border:0;display:block;background:#fff;opacity:1;transition:opacity .16s ease;}' +
            '.product-detail-tab-frame.is-loading{opacity:0;}' +
            '.product-detail-create-actions{display:flex;justify-content:flex-end;gap:10px;padding-top:16px;border-top:1px solid #f0f0f0;margin-top:16px;}' +
            '.product-detail-header{display:flex;gap:18px;align-items:flex-start;margin-bottom:20px;}' +
            '.product-detail-cover{width:120px;height:120px;object-fit:cover;border-radius:8px;border:1px solid #d9d9d9;background:#fafafa;cursor:zoom-in;}' +
            '.product-detail-meta{flex:1;min-width:0;}.product-detail-name{margin:0 0 8px;font-size:20px;line-height:1.4;color:#262626;}' +
            '.product-detail-package{font-size:14px;color:#666;margin-bottom:10px;line-height:1.7;}' +
            '.product-detail-tags{display:flex;flex-wrap:wrap;gap:8px;align-items:center;margin-bottom:10px;}' +
            '.product-detail-tags-left{display:flex;flex-wrap:wrap;gap:8px;align-items:center;min-width:0;}' +
            '.product-detail-badge{display:inline-flex;align-items:center;padding:5px 10px 5px 7px;border-radius:2px;font-size:13px;line-height:1;border:1px solid transparent;background:#f7f9fc;color:#2d3548;}' +
            '.product-detail-badge .icon{width:14px;height:14px;border-radius:50%;display:inline-flex;align-items:center;justify-content:center;font-size:10px;color:#fff;margin-right:6px;font-weight:700;flex-shrink:0;}' +
            '.product-detail-badge.allow{background:#edfff3;border-color:#93dfb1;color:#1a9b52;}' +
            '.product-detail-badge.allow .icon{background:#27b35d;}' +
            '.product-detail-badge.block{background:#fff8ed;border-color:#f5c77f;color:#d2841d;}' +
            '.product-detail-badge.block .icon{background:#f0a13f;}' +
            '.product-detail-badge.info{background:#eef6ff;border-color:#b7d8ff;color:#1f6fd1;}' +
            '.product-detail-badge.info .icon{background:#4b91ff;}' +
            '.product-detail-badge.hot{background:#fff1f0;border-color:#ffccc7;color:#cf1322;}' +
            '.product-detail-badge.hot .icon{background:#ff7875;}' +
            '.product-detail-badge.gray{background:#f6f8fb;border-color:#d9dde5;color:#6b7280;}' +
            '.product-detail-badge.gray .icon{background:#adb5bd;}' +
            '.product-detail-times{display:flex;flex-wrap:wrap;gap:18px;font-size:13px;color:#8c8c8c;}' +
            '.product-detail-section{margin-bottom:20px;}.product-detail-section-title{margin:0;padding:15px 16px;font-size:16px;color:#1890ff;border-bottom:1px solid #f0f0f0;background:#fafafa;border-left:4px solid #1890ff;line-height:1;display:flex;align-items:center;justify-content:space-between;gap:12px;}' +
            '.product-detail-section-title-text{flex:0 0 auto;}' +
            '.product-detail-section-title-tip{margin-left:auto;font-size:12px;color:#8c8c8c;line-height:1.6;font-weight:400;text-align:right;}' +
            '.product-detail-table{width:100%;border-collapse:collapse;table-layout:fixed;}.product-detail-table td{border-bottom:1px solid #f0f0f0;}' +
            '.product-detail-label{width:18%;padding:12px 18px;background:#fafafa;color:#595959;text-align:right;vertical-align:top;word-break:break-all;}' +
            '.product-detail-value{width:32%;padding:12px 18px;border-left:1px solid #f0f0f0;vertical-align:top;position:relative;line-height:1.7;word-break:break-word;}' +
            '.product-detail-value[colspan]{width:auto;}.product-detail-editable{transition:background-color .2s ease;}.product-detail-editable:hover{background:#f6ffed;}' +
            '.product-detail-empty{color:#bfbfbf;}.product-detail-text{white-space:pre-wrap;color:#333;min-height:24px;}' +
            '.product-detail-inline-input,.product-detail-inline-textarea,.product-detail-inline-select{width:100%;box-sizing:border-box;border:0;border-radius:0;padding:0;font-size:14px;color:#333;background:transparent;box-shadow:none;}' +
            '.product-detail-inline-input:focus,.product-detail-inline-textarea:focus,.product-detail-inline-select:focus{outline:none;border:0;box-shadow:none;background:transparent;}' +
            '.product-detail-inline-textarea{min-height:92px;resize:vertical;line-height:1.7;}' +
            '.product-detail-inline-note{padding:0;background:transparent;color:#8c8c8c;line-height:1.7;}' +
            '.product-detail-region-editor{position:relative;min-height:28px;}' +
            '.product-detail-region-preview{display:flex;flex-wrap:wrap;gap:8px;align-items:center;min-height:28px;cursor:text;}' +
            '.product-detail-region-chip{display:inline-flex;align-items:center;padding:5px 10px 5px 7px;border-radius:2px;font-size:13px;line-height:1;border:1px solid transparent;background:#f7f9fc;color:#2d3548;}' +
            '.product-detail-region-chip .icon{width:14px;height:14px;border-radius:50%;display:inline-flex;align-items:center;justify-content:center;font-size:10px;color:#fff;margin-right:6px;font-weight:700;flex-shrink:0;}' +
            '.product-detail-region-chip.allow{background:#edfff3;border-color:#93dfb1;color:#1a9b52;}' +
            '.product-detail-region-chip.allow .icon{background:#27b35d;}' +
            '.product-detail-region-chip.block{background:#fff8ed;border-color:#f5c77f;color:#d2841d;}' +
            '.product-detail-region-chip.block .icon{background:#f0a13f;}' +
            '.product-detail-region-textarea{display:none;}' +
            '.product-detail-upload-wrap{position:relative;}.product-detail-upload-wrap .product-detail-inline-input{padding-right:42px;}' +
            '.product-detail-upload-btn{position:absolute;right:12px;top:50%;transform:translateY(-50%);font-size:18px;color:#999;cursor:pointer;}' +
            '.product-detail-upload-link{margin-left:12px;color:#1890ff;cursor:pointer;text-decoration:none;}' +
            '.product-detail-value .layui-cascader,.product-detail-value .layui-form-select,.product-detail-value .layui-unselect{width:100%;height:auto !important;min-height:0 !important;border:0 !important;background:transparent !important;box-shadow:none !important;margin:0 !important;}' +
            '.product-detail-value .layui-cascader .layui-input,.product-detail-value .layui-form-select .layui-input,.product-detail-value .layui-unselect{border:0 !important;background:transparent !important;padding:0 !important;height:auto !important;min-height:0 !important;line-height:1.7 !important;box-shadow:none !important;margin:0 !important;}' +
            '.product-detail-value .layui-cascader .layui-edge,.product-detail-value .layui-form-select .layui-edge{right:0;}' +
            '.product-detail-channel-select{width:100%;height:28px;border:0!important;border-color:transparent!important;background-color:transparent!important;background-image:url("data:image/svg+xml,%3Csvg xmlns=\'http://www.w3.org/2000/svg\' width=\'12\' height=\'8\' viewBox=\'0 0 12 8\'%3E%3Cpath fill=\'%23333\' d=\'M1.41.59 6 5.17 10.59.59 12 2l-6 6-6-6z\'/%3E%3C/svg%3E");background-repeat:no-repeat;background-position:right 6px center;background-size:10px 7px;color:#333;font-size:14px;line-height:1.7;outline:none;box-shadow:none!important;padding:0 24px 0 0;margin:0;appearance:none!important;-webkit-appearance:none!important;-moz-appearance:none!important;}' +
            '.product-detail-channel-select:focus{border:0!important;outline:none;box-shadow:none!important;background:transparent!important;}' +
            '.product-detail-tag-editor{position:relative;min-height:28px;}' +
            '.product-detail-tag-preview{display:flex;flex-wrap:wrap;gap:6px;align-items:center;min-height:28px;cursor:text;}' +
            '.product-detail-tag-chip{display:inline-block;padding:2px 6px;border-radius:3px;font-size:12px;line-height:18px;}' +
            '.product-detail-tag-input{display:none;}' +
            '.product-detail-group-editor{display:flex;flex-wrap:wrap;align-items:center;gap:8px 12px;min-height:28px;}' +
            '.product-detail-group-option{display:inline-flex;align-items:center;gap:5px;color:#333;font-size:13px;line-height:22px;cursor:pointer;user-select:none;}' +
            '.product-detail-group-option input{margin:0;accent-color:#1890ff;}' +
            '.product-detail-group-note{flex:0 0 100%;color:#8c8c8c;font-size:12px;line-height:1.6;}' +
            '.product-detail-inline-tags{display:flex;flex-wrap:wrap;gap:6px;}.product-detail-inline-tag{display:inline-block;padding:2px 8px;border-radius:12px;background:#e6f7ff;color:#1890ff;font-size:12px;line-height:18px;}' +
            '.product-detail-status{display:inline-block;padding:2px 8px;border-radius:4px;font-size:12px;line-height:18px;color:#fff;}' +
            '.product-detail-status.success{background:#52c41a;}.product-detail-status.warning{background:#fa8c16;}.product-detail-status.primary{background:#1890ff;}.product-detail-status.gray{background:#bfbfbf;}' +
            '.product-detail-image-editor{position:relative;}' +
            '.product-detail-image-grid{display:flex;flex-wrap:wrap;gap:12px;}' +
            '.product-detail-image-card{position:relative;width:96px;height:96px;border-radius:6px;overflow:hidden;border:1px solid #d9d9d9;background:#fafafa;display:flex;align-items:center;justify-content:center;}' +
            '.product-detail-image-card img{width:100%;height:100%;object-fit:cover;display:block;}' +
            '.product-detail-image-delete{position:absolute;top:4px;right:4px;width:18px;height:18px;border-radius:50%;background:rgba(0,0,0,.55);color:#fff;font-size:12px;line-height:18px;text-align:center;cursor:pointer;z-index:2;}' +
            '.product-detail-image-delete:hover{background:rgba(0,0,0,.72);}' +
            '.product-detail-image-mask{position:absolute;left:0;right:0;bottom:0;height:24px;background:rgba(0,0,0,.55);display:flex;align-items:center;justify-content:center;gap:6px;color:#fff;font-size:11px;opacity:0;transition:opacity .2s ease;}' +
            '.product-detail-image-card:hover .product-detail-image-mask{opacity:1;}' +
            '.product-detail-image-action{color:#fff;cursor:pointer;display:inline-flex;align-items:center;gap:2px;font-size:11px;line-height:1;}' +
            '.product-detail-image-action:hover{color:#fff;opacity:.88;}' +
            '.product-detail-image-upload{border-style:dashed;color:#8c8c8c;cursor:pointer;flex-direction:column;gap:6px;}' +
            '.product-detail-image-upload .plus{font-size:26px;line-height:1;}' +
            '.product-detail-image-upload .txt{font-size:12px;line-height:1;}' +
            '.product-detail-image-edit-input{display:none;margin-top:10px;}' +
            '.product-detail-image-edit-input.active{display:block;}' +
            '.product-detail-rich-card{position:relative;border:1px solid #f0f0f0;border-radius:8px;background:#fff;overflow:hidden;}' +
            '.product-detail-rich-card:hover{border-color:#b7eb8f;box-shadow:0 0 0 1px #f6ffed inset;}' +
            '.product-detail-rich-hint{display:none;}' +
            '.product-detail-rich-body{padding:16px;line-height:1.8;color:#333;min-height:180px;word-break:break-word;}' +
            '.product-detail-process-step{padding:14px 16px;border-bottom:1px solid #f0f0f0;}' +
            '.product-detail-process-step:last-child{border-bottom:none;}' +
            '.product-detail-process-title{font-size:15px;font-weight:600;color:#333;margin-bottom:8px;}' +
            '.product-detail-process-content{line-height:1.8;color:#333;}' +
            '.product-detail-preview-link{color:#1890ff;cursor:pointer;text-decoration:none;}.product-detail-preview-link:hover{color:#40a9ff;}' +
            '.product-detail-preview-meta{font-size:12px;color:#8c8c8c;line-height:1.6;}.product-detail-note{margin-top:6px;font-size:12px;color:#8c8c8c;}' +
            '.product-custom-fields{display:flex;flex-direction:column;gap:12px;}' +
            '.product-custom-fields-empty{padding:14px 16px;border:1px dashed #d9d9d9;border-radius:8px;background:#fafafa;color:#8c8c8c;font-size:13px;line-height:1.8;}' +
            '.product-custom-field-card{border:1px solid #e8e8e8;border-radius:8px;padding:12px;background:#fcfcfc;}' +
            '.product-custom-field-grid{display:grid;grid-template-columns:repeat(3,minmax(0,1fr));gap:10px 14px;}' +
            '.product-custom-field-full{grid-column:1 / -1;}' +
            '.product-custom-field-label{display:block;font-size:12px;color:#8c8c8c;margin-bottom:4px;}' +
            '.product-custom-field-input,.product-custom-field-select,.product-custom-field-textarea{width:100%;box-sizing:border-box;border:1px solid #d9d9d9;border-radius:6px;padding:8px 10px;font-size:13px;line-height:1.5;background:#fff;}' +
            '.product-custom-field-textarea{min-height:84px;resize:vertical;}' +
            '.product-custom-field-actions{display:flex;justify-content:space-between;align-items:center;margin-top:10px;gap:10px;}' +
            '.product-custom-field-action-buttons{display:flex;align-items:center;gap:12px;}' +
            '.product-custom-field-sort{color:#1677ff;cursor:pointer;font-size:12px;}' +
            '.product-custom-field-save{color:#52c41a;cursor:pointer;font-size:12px;}' +
            '.product-custom-field-remove{color:#ff4d4f;cursor:pointer;font-size:12px;}' +
            '.product-custom-field-toolbar{display:flex;align-items:center;gap:12px;flex-wrap:wrap;}' +
            '.product-custom-field-add{display:inline-flex;align-items:center;justify-content:center;height:34px;padding:0 14px;border:1px dashed #91caff;border-radius:8px;background:#f0f8ff;color:#1677ff;font-size:13px;cursor:pointer;}' +
            '.product-custom-field-save-btn{display:inline-flex;align-items:center;justify-content:center;height:34px;padding:0 14px;border:1px solid #b7eb8f;border-radius:8px;background:#f6ffed;color:#52c41a;font-size:13px;cursor:pointer;}' +
            '.product-custom-field-help{font-size:12px;color:#8c8c8c;line-height:1.8;}';
        hostDocument.head.appendChild(style);
    }

    var state = null;
    var configs = {
        id: {label: '产品ID', editor: 'readonly'},
        create_time: {label: '创建时间', editor: 'readonly'},
        update_time: {label: '更新时间', editor: 'readonly'},
        name: {label: '产品名称', editor: 'text'},
        yys: {label: '运营商', editor: 'select', options: [['移动', '移动'], ['联通', '联通'], ['电信', '电信'], ['广电', '广电']]},
        api_name: {label: '渠道商', editor: 'channel'},
        number: {label: '上游产品编码', editor: 'text'},
        yuezu: {label: '月租', editor: 'number'},
        flow: {label: '通用流量', editor: 'number'},
        dingxiang: {label: '定向流量', editor: 'number'},
        call: {label: '通话时长', editor: 'number'},
        sms: {label: '短信条数', editor: 'number'},
        guishudi: {label: '归属地', editor: 'text'},
        kefa: {label: '可发区', editor: 'textarea'},
        jinfa: {label: '禁发区', editor: 'textarea'},
        peisong: {label: '配送方式', editor: 'text'},
        kaika: {label: '开卡方式', editor: 'text'},
        age: {label: '适用年龄', editor: 'text'},
        heyue: {label: '合约期', editor: 'text'},
        tags: {label: '产品标签', editor: 'text'},
        visible_group_ids: {label: '显示分组', editor: 'groups'},
        status: {label: '上下架', editor: 'select', options: [['1', '上架'], ['0', '下架']]},
        is_open: {label: '开放API', editor: 'select', options: [['1', '开启'], ['0', '关闭']]},
        selectNumber: {label: '选号', editor: 'select', options: [['1', '支持'], ['0', '不支持']]},
        iccid_auto_push: {label: 'ICCID推送', editor: 'select', options: [['1', '开启'], ['0', '关闭']]},
        isHot: {label: '热门', editor: 'select', options: [['1', '是'], ['0', '否']]},
        is_recommend: {label: '推荐', editor: 'select', options: [['1', '是'], ['0', '否']]},
        is_id_photo: {label: '三证', editor: 'select', options: [['1', '开启'], ['0', '关闭']]},
        is_four_photo: {label: '四证', editor: 'select', options: [['1', '开启'], ['0', '关闭']]},
        four_photo_title: {label: '四证标题', editor: 'text'},
        four_photo: {label: '四证链接', editor: 'upload_text'},
        product_image: {label: '产品主图', editor: 'image'},
        detail_images: {label: '详情图', editor: 'images'},
        commission: {label: '佣金', editor: 'number'},
        js_type: {label: '结算模式', editor: 'select', options: [['1', '秒返'], ['2', '次月返']]},
        card_type: {label: '卡类型', editor: 'select', options: [['0', '免费卡'], ['1', '付费卡']]},
        card_price: {label: '卡费金额', editor: 'number'},
        first_chongzhi: {label: '首充金额', editor: 'number'},
        external_order_url: {label: '三方下单地址', editor: 'text'},
        product_custom_fields: {label: '自定义字段JSON', editor: 'custom_fields'},
        rule: {label: '首充规则', editor: 'textarea'},
        js_require: {label: '结算要求', editor: 'textarea'},
        mark: {label: '备注信息', editor: 'textarea'},
        policy_order_security_check: {label: '安全校验', editor: 'select', options: [['', '跟随系统'], ['0', '关闭'], ['1', '开启']]},
        policy_shop_order_verify: {label: '下单验证', editor: 'select', options: [['', '跟随系统'], ['none', '关闭'], ['sms', '短信验证码'], ['image', '图形验证码']]},
        policy_shop_order_idcard_verify: {label: '下单要素校验', editor: 'select', options: [['', '跟随系统'], ['none', '关闭'], ['two', '二要素'], ['three', '三要素']]},
        policy_product_ship_sms_notice: {label: '短信通知-发货', editor: 'select', options: [['', '跟随系统'], ['0', '关闭'], ['1', '开启']]},
        policy_order_review_failed_sms_notice: {label: '短信通知-审核失败', editor: 'select', options: [['', '跟随系统'], ['0', '关闭'], ['1', '开启']]},
        product_popup: {label: '产品弹窗', editor: 'textarea'},
        submit_success_info: {label: '提单后信息', editor: 'textarea'},
        order_process: {label: '下单流程', editor: 'textarea'}
    };
    var basicSections = [
        {title: '基本信息', rows: [['name', 'yys'], ['api_name', 'number'], ['status', 'guishudi'], ['yuezu', 'flow'], ['dingxiang', 'call'], ['kefa'], ['jinfa'], ['peisong', 'kaika'], ['age', 'heyue'], ['tags'], ['visible_group_ids'], ['selectNumber', 'iccid_auto_push'], ['is_recommend', 'is_open'], ['isHot', 'is_id_photo'], ['is_four_photo', 'four_photo_title'], ['four_photo'], ['product_image'], ['detail_images']]},
        {title: '结算信息', rows: [['commission', 'js_type'], ['card_type', 'card_price'], ['first_chongzhi'], ['rule'], ['js_require'], ['mark']]},
        {title: '三方下单', type: 'single_input', field: 'external_order_url'},
        {title: '下单自定义字段', type: 'custom_fields', field: 'product_custom_fields'},
        {title: '功能策略', rows: [['policy_order_security_check', 'policy_shop_order_verify'], ['policy_shop_order_idcard_verify', 'policy_product_ship_sms_notice'], ['policy_order_review_failed_sms_notice']]}
    ];
    var detailTabs = [
        {key: 'basic', label: '产品资料'},
        {key: 'popup', label: '产品弹窗', field: 'product_popup'},
        {key: 'process', label: '下单流程', field: 'order_process'},
        {key: 'submit_info', label: '提单后信息', field: 'submit_success_info'}
    ];
    var channelState = {
        apiOptionList: [],
        selfChannelList: [],
        meta: {},
        loaded: false,
        loading: false,
        callbacks: [],
        cascaderIns: null
    };
    function createEmptyProduct() {
        return {
            id: 0,
            name: '',
            yys: '',
            api_name: '',
            api_name_display: '',
            api_config_id: 0,
            self_channel_id: 0,
            number: '',
            yuezu: '',
            flow: '',
            dingxiang: '',
            call: '',
            sms: '',
            guishudi: '全国',
            kefa: '',
            jinfa: '',
            peisong: '包邮',
            kaika: '线上激活',
            age: '18-65岁',
            heyue: '无合约',
            tags: '',
            visible_group_ids: '',
            status: '1',
            is_open: '1',
            selectNumber: '0',
            iccid_auto_push: '0',
            isHot: '0',
            is_recommend: '0',
            is_id_photo: '0',
            is_four_photo: '0',
            four_photo_title: '',
            four_photo: '',
            product_image: '',
            product_image_display: '',
            detail_images: '',
            detail_images_display: [],
            commission: '',
            js_type: '1',
            card_type: '0',
            card_price: '0',
            first_chongzhi: '',
            external_order_url: '',
            product_custom_fields: '',
            rule: '',
            js_require: '',
            mark: '',
            policy_order_security_check: '',
            policy_shop_order_verify: '',
            policy_shop_order_idcard_verify: '',
            policy_product_ship_sms_notice: '',
            policy_order_review_failed_sms_notice: '',
            product_popup: '',
            submit_success_info: '',
            order_process: '[]',
            create_time: '',
            update_time: ''
        };
    }

    function $(selector) { return layui.jquery(selector); }
    function layer() {
        var hostWindow = getHostWindow();
        return (hostWindow.layui && hostWindow.layui.layer) || hostWindow.layer || window.layuiLayer;
    }
    function lockDrawerScroll() {
        layui.jquery('body').css('overflow', 'hidden');
        var hostDocument = getHostDocument();
        if (hostDocument !== document) {
            layui.jquery(hostDocument.body).css('overflow', 'hidden');
        }
    }
    function unlockDrawerScroll() {
        layui.jquery('body').css('overflow', '');
        var hostDocument = getHostDocument();
        if (hostDocument !== document) {
            layui.jquery(hostDocument.body).css('overflow', '');
        }
    }
    function normalizeDrawerLayer(layero, index) {
        var hostDocument = getHostDocument();
        var $hostDocument = layui.jquery(hostDocument);
        var $shade = $hostDocument.find('#layui-layer-shade' + index);
        $shade.css({
            position: 'fixed',
            top: 0,
            left: 0,
            right: 0,
            bottom: 0,
            width: '100vw',
            height: '100vh'
        });
        layero.css({
            position: 'fixed',
            top: 0,
            right: 0,
            bottom: 0,
            height: '100vh',
            'max-height': '100vh'
        });
        layero.find('.layui-layer-content').css({
            'overflow-y': 'auto',
            'overflow-x': 'hidden',
            'height': 'calc(100vh - 42px)',
            'max-height': 'calc(100vh - 42px)'
        });
    }
    function eventTargets() {
        var $targets = layui.jquery(document);
        var hostDocument = getHostDocument();
        if (hostDocument !== document) {
            $targets = $targets.add(hostDocument);
        }
        return $targets;
    }
    function esc(v) { return String(v == null ? '' : v).replace(/&/g, '&amp;').replace(/</g, '&lt;').replace(/>/g, '&gt;').replace(/"/g, '&quot;').replace(/'/g, '&#39;'); }
    function jsEsc(v) { return String(v == null ? '' : v).replace(/\\/g, '\\\\').replace(/'/g, '\\\''); }
    function text(v) { return String(v == null ? '' : v).replace(/<[^>]+>/g, '').trim(); }

    function normalizeImages(product) {
        if (Array.isArray(product.detail_images_display) && product.detail_images_display.length) return product.detail_images_display.slice();
        if (Array.isArray(product.detail_images) && product.detail_images.length) return product.detail_images.slice();
        var raw = product.detail_images || '';
        if (!raw) return [];
        try { if (String(raw).charAt(0) === '[') return JSON.parse(raw) || []; } catch (e) {}
        return String(raw).split(/\r\n|\r|\n/).map(function (item) { return item.trim(); }).filter(Boolean);
    }

    function normalizeCustomFieldItem(item) {
        item = item || {};
        var normalized = {
            name: String(item.name || '').trim(),
            label: String(item.label || '').trim(),
            type: String(item.type || 'text').trim() || 'text',
            required: String(item.required || item.required === 0 ? item.required : '0') === '1' ? '1' : '0',
            placeholder: String(item.placeholder || '').trim(),
            options: []
        };
        if (normalized.type !== 'select' && normalized.type !== 'textarea' && normalized.type !== 'number' && normalized.type !== 'date') {
            normalized.type = 'text';
        }
        if (Array.isArray(item.options)) {
            normalized.options = item.options.map(function (option) {
                if (typeof option === 'string') {
                    return option.trim();
                }
                if (option && typeof option === 'object') {
                    return String(option.label || option.value || '').trim();
                }
                return '';
            }).filter(Boolean);
        }
        return normalized;
    }

    function parseCustomFields(rawValue) {
        var raw = String(rawValue || '').trim();
        if (!raw) {
            return [];
        }
        try {
            var parsed = JSON.parse(raw);
            if (!Array.isArray(parsed)) {
                return [];
            }
            return parsed.map(normalizeCustomFieldItem);
        } catch (e) {
            return [];
        }
    }

    function stringifyCustomFields(fields) {
        fields = Array.isArray(fields) ? fields.map(normalizeCustomFieldItem).filter(function (item) {
            return item.name || item.label;
        }) : [];
        return fields.length ? JSON.stringify(fields, null, 2) : '';
    }

    function generateCustomFieldName(fields) {
        fields = Array.isArray(fields) ? fields : [];
        var index = fields.length + 1;
        var name = '';
        var exists = {};
        fields.forEach(function (item) {
            exists[String(item.name || '').trim()] = true;
        });
        do {
            name = 'custom_field_' + index;
            index += 1;
        } while (exists[name]);
        return name;
    }

    function customFieldTypeOptions(current) {
        return [
            ['text', '文本框'],
            ['textarea', '多行文本'],
            ['select', '下拉框'],
            ['number', '数字'],
            ['date', '日期']
        ].map(function (item) {
            return '<option value="' + esc(item[0]) + '"' + (item[0] === current ? ' selected' : '') + '>' + esc(item[1]) + '</option>';
        }).join('');
    }

    function renderCustomFieldsBuilder(product) {
        var fields = parseCustomFields(product.product_custom_fields || '');
        var html = '<div class="product-custom-fields">';
        if (!fields.length) {
            html += '<div class="product-custom-fields-empty">暂未配置自定义字段。点击下方“新增字段”后，前台下单页会自动显示在详细地址下方。</div>';
        } else {
            fields.forEach(function (item, index) {
                html += '<div class="product-custom-field-card" data-custom-field-index="' + index + '">';
                html += '<div class="product-custom-field-grid">';
                html += '<div><label class="product-custom-field-label">字段标题</label><input type="text" class="product-custom-field-input" value="' + esc(item.label) + '" placeholder="例如：宽带账号" onblur="window.productDetailEditorUpdateCustomField(' + index + ', \'label\', this.value)"></div>';
                html += '<div><label class="product-custom-field-label">字段类型</label><select class="product-custom-field-select" onchange="window.productDetailEditorUpdateCustomField(' + index + ', \'type\', this.value)">' + customFieldTypeOptions(item.type) + '</select></div>';
                html += '<div><label class="product-custom-field-label">是否必填</label><select class="product-custom-field-select" onchange="window.productDetailEditorUpdateCustomField(' + index + ', \'required\', this.value)"><option value="0"' + (item.required !== '1' ? ' selected' : '') + '>选填</option><option value="1"' + (item.required === '1' ? ' selected' : '') + '>必填</option></select></div>';
                html += '<div class="product-custom-field-full"><label class="product-custom-field-label">输入框内提醒内容</label><input type="text" class="product-custom-field-input" value="' + esc(item.placeholder) + '" placeholder="例如：请输入宽带账号" onblur="window.productDetailEditorUpdateCustomField(' + index + ', \'placeholder\', this.value)"></div>';
                html += '<div class="product-custom-field-full"' + (item.type === 'select' ? '' : ' style="display:none;"') + '><label class="product-custom-field-label">下拉选项</label><textarea class="product-custom-field-textarea" placeholder="每行一个选项，例如：&#10;新装&#10;续费&#10;升级" onblur="window.productDetailEditorUpdateCustomField(' + index + ', \'options\', this.value)">' + esc((item.options || []).join('\n')) + '</textarea></div>';
                html += '</div>';
                html += '<div class="product-custom-field-actions"><span class="product-detail-note">前台展示顺序按这里从上到下排列</span><div class="product-custom-field-action-buttons"><span class="product-custom-field-sort" onclick="window.productDetailEditorMoveCustomField(' + index + ', -1)">上移</span><span class="product-custom-field-sort" onclick="window.productDetailEditorMoveCustomField(' + index + ', 1)">下移</span><span class="product-custom-field-remove" onclick="window.productDetailEditorRemoveCustomField(' + index + ')">删除字段</span></div></div>';
                html += '</div>';
            });
        }
        html += '<div class="product-custom-field-toolbar"><button type="button" class="product-custom-field-add" onclick="window.productDetailEditorAddCustomField()">新增字段</button><button type="button" class="product-custom-field-save-btn" onclick="window.productDetailEditorSaveCustomFields()">保存配置</button></div>';
        html += '</div>';
        return html;
    }

    function packageText(product) {
        var parts = [];
        var yuezu = parseFloat(product.yuezu || 0);
        var call = parseFloat(product.call || 0);
        var dingxiang = parseFloat(product.dingxiang || 0);
        var sms = parseInt(product.sms || 0, 10);
        if (!isNaN(yuezu) && yuezu > 0) parts.push(yuezu + '元/月');
        parts.push((product.flow || 0) + 'GB通用');
        if (!isNaN(dingxiang) && dingxiang > 0) parts.push(dingxiang + 'GB定向');
        if (!isNaN(call) && call > 0) parts.push(call + '分钟通话');
        if (!isNaN(sms) && sms > 0) parts.push(sms + '条短信');
        return parts.join(' + ');
    }

    function badge(textValue, cls, withIcon) {
        var icon = '•';
        if (cls === 'allow') icon = '✓';
        if (cls === 'block') icon = '✕';
        if (cls === 'info') icon = 'i';
        if (cls === 'hot') icon = '!';
        if (cls === 'gray') icon = '#';
        return '<span class="product-detail-badge ' + cls + '">' + (withIcon === false ? '' : ('<span class="icon">' + icon + '</span>')) + '<span>' + esc(textValue) + '</span></span>';
    }

    function renderReadonlyValue(field, product) {
        var value = product[field];
        if (field === 'product_custom_fields') {
            var fields = parseCustomFields(value);
            if (!fields.length) return '<span class="product-detail-empty">未设置</span>';
            return fields.map(function (item) {
                return '<div>' + esc(item.label || item.name || '-') + ' / ' + esc(item.type) + (item.required === '1' ? ' / 必填' : ' / 选填') + '</div>';
            }).join('');
        }
        if (field === 'api_name') return esc(product.api_name_display || product.api_name || '自营');
        if (field === 'status') return badge(String(value) === '1' ? '上架' : '下架', String(value) === '1' ? 'success' : 'gray');
        if (field === 'is_open') return badge(String(value) === '1' ? '开启' : '关闭', String(value) === '1' ? 'primary' : 'gray');
        if (field === 'selectNumber') return badge(String(value) === '1' ? '支持' : '不支持', String(value) === '1' ? 'primary' : 'gray');
        if (field === 'iccid_auto_push') return badge(String(value) === '1' ? '开启' : '关闭', String(value) === '1' ? 'success' : 'gray');
        if (field === 'isHot' || field === 'is_recommend') return badge(String(value) === '1' ? '是' : '否', String(value) === '1' ? 'warning' : 'gray');
        if (field === 'is_id_photo' || field === 'is_four_photo') return badge(String(value) === '1' ? '开启' : '关闭', String(value) === '1' ? 'warning' : 'gray');
        if (field === 'card_type') return esc(String(value) === '1' ? '付费卡' : '免费卡');
        if (field === 'js_type') return esc(String(value) === '1' ? '秒返' : '次月返');
        if (field.indexOf('policy_') === 0) {
            if (field === 'policy_shop_order_verify') return esc(({'': '跟随系统', 'none': '关闭', 'sms': '短信验证码', 'image': '图形验证码'})[value || ''] || (value || '-'));
            return esc(value === '' ? '跟随系统' : (String(value) === '1' ? '开启' : '关闭'));
        }
        return value === '' || value == null ? '<span class="product-detail-empty">未设置</span>' : esc(value);
    }

    function isSelfChannel(product) {
        return String(product.api_name_display || product.api_name || '自营').indexOf('自营') === 0;
    }

    function currentFieldValue(field, product) {
        if (field === 'api_name') return product.api_name_display || product.api_name || '自营';
        if (field === 'detail_images') return normalizeImages(product).join('\n');
        return product[field] == null ? '' : String(product[field]);
    }

    function renderImageField(field, product) {
        if (field === 'product_image') {
            var img = product.product_image_display || product.product_image || product.display_image || '';
            if (!img) {
                return '<div class="product-detail-image-editor">' +
                    '<div class="product-detail-image-grid">' +
                        '<div id="productDetailMainImageUploadBtn" class="product-detail-image-card product-detail-image-upload">' +
                            '<span class="plus">+</span><span class="txt">上传</span>' +
                        '</div>' +
                    '</div>' +
                    '<input type="text" class="product-detail-inline-input product-detail-image-edit-input js-detail-inline-input" data-field="product_image" value="" placeholder="请输入产品主图地址">' +
                '</div>';
            }
            return '<div class="product-detail-image-editor">' +
                '<div class="product-detail-image-grid">' +
                    '<div class="product-detail-image-card">' +
                        (product.product_image ? '<span class="product-detail-image-delete js-main-image-delete" title="删除">×</span>' : '') +
                        '<img src="' + esc(img) + '" alt="产品主图">' +
                        '<div class="product-detail-image-mask">' +
                            '<span class="product-detail-image-action js-product-detail-preview" data-images="' + esc(JSON.stringify([img])) + '"><i class="layui-icon layui-icon-search"></i><span>预览</span></span>' +
                            '<span id="productDetailMainImageUploadBtn" class="product-detail-image-action"><i class="layui-icon layui-icon-edit"></i><span>修改</span></span>' +
                        '</div>' +
                    '</div>' +
                '</div>' +
                '<input type="text" class="product-detail-inline-input product-detail-image-edit-input js-detail-inline-input" data-field="product_image" value="' + esc(product.product_image || '') + '" placeholder="请输入产品主图地址">' +
                '</div>';
        }
        var images = normalizeImages(product);
        var cards = images.map(function (img, index) {
            return '<div class="product-detail-image-card">' +
                '<span class="product-detail-image-delete js-detail-image-delete" data-index="' + index + '" title="删除">×</span>' +
                '<img src="' + esc(img) + '" alt="详情图">' +
                '<div class="product-detail-image-mask">' +
                    '<span class="product-detail-image-action js-product-detail-preview" data-images="' + esc(JSON.stringify(images)) + '" data-start="' + index + '"><i class="layui-icon layui-icon-search"></i><span>预览</span></span>' +
                    '<span class="product-detail-image-action js-detail-image-replace" data-index="' + index + '"><i class="layui-icon layui-icon-edit"></i><span>修改</span></span>' +
                '</div>' +
            '</div>';
        }).join('');
        cards += '<div id="productDetailDetailImagesUploadBtn" class="product-detail-image-card product-detail-image-upload">' +
            '<span class="plus">+</span><span class="txt">上传</span>' +
            '</div>';
        return '<div class="product-detail-image-editor">' +
        '<div class="product-detail-image-grid">' + cards + '</div>' +
        '<textarea class="product-detail-inline-textarea product-detail-image-edit-input js-detail-inline-textarea" data-field="detail_images" placeholder="一行一个详情图地址">' + esc(currentFieldValue('detail_images', product)) + '</textarea>' +
        '</div>';
    }

    function renderInlineSelect(field, cfg, product) {
        var value = currentFieldValue(field, product);
        return '<select class="product-detail-inline-select js-detail-inline-select" data-field="' + esc(field) + '">' +
            (cfg.options || []).map(function (item) {
                var selected = String(item[0]) === String(value) ? ' selected' : '';
                return '<option value="' + esc(item[0]) + '"' + selected + '>' + esc(item[1]) + '</option>';
            }).join('') +
            '</select>';
    }

    function regionItems(value) {
        return String(value || '')
            .split(/\r\n|\r|\n|,|，|;|；|、/)
            .map(function (item) { return item.trim(); })
            .filter(Boolean);
    }

    function tagItems(value) {
        return String(value || '')
            .split(/\r\n|\r|\n|,|，|;|；|、|\s+/)
            .map(function (item) { return item.trim(); })
            .filter(Boolean);
    }

    function normalizeTagValue(value) {
        return tagItems(value).join(',');
    }

    function agentGroupOptions() {
        return Array.isArray(window.productAgentGroupOptions) ? window.productAgentGroupOptions : [];
    }

    function parseVisibleGroupIds(value) {
        var seen = {};
        return String(value || '')
            .split(/[,\s]+/)
            .map(function (item) { return parseInt(item, 10); })
            .filter(function (id) {
                if (!id || id <= 0 || seen[id]) return false;
                seen[id] = true;
                return true;
            })
            .map(function (id) { return String(id); });
    }

    function normalizeVisibleGroupValue(value) {
        return parseVisibleGroupIds(value).join(',');
    }

    function formatVisibleGroupText(value) {
        var ids = parseVisibleGroupIds(value);
        if (!ids.length) return '全部分组';
        var groups = agentGroupOptions();
        var names = {};
        groups.forEach(function (group) {
            names[String(group.id || '')] = group.name || '';
        });
        return ids.map(function (id) {
            return names[id] || ('分组#' + id);
        }).join('、');
    }

    function fieldPlaceholder(field, cfg) {
        var custom = cfg && cfg.placeholder;
        if (custom) return custom;
        var map = {
            name: '请输入产品名称',
            number: '请输入上游产品编码',
            yuezu: '请输入月租',
            flow: '请输入通用流量',
            dingxiang: '请输入定向流量',
            call: '请输入通话时长',
            guishudi: '请输入归属地',
            kefa: '输入可发省份，下单页根据设置省份，可自适应显示',
            jinfa: '支持省市，下单页自动排除',
            peisong: '请输入配送方式',
            kaika: '请输入开卡方式',
            age: '请输入适用年龄',
            heyue: '请输入合约期',
            tags: '多标签逗号隔开',
            four_photo_title: '请输入四证标题',
            four_photo: '请输入四证链接',
            commission: '请输入佣金',
            card_price: '请输入卡费金额',
            first_chongzhi: '请输入首充金额',
            external_order_url: '请填写二维码跳转链接，如产品最终为站外扫码下单，下单页会出现选号按钮，点击跳转到链接中',
            product_custom_fields: '请输入JSON数组，例如：[{"name":"wechat","label":"微信号","type":"text","required":1,"placeholder":"请输入微信号"}]',
            rule: '请输入首充规则',
            js_require: '请输入结算要求',
            mark: '请输入备注信息',
            product_image: '请输入产品主图地址',
            detail_images: '一行一个详情图地址'
        };
        return map[field] || (cfg && cfg.label ? ('请输入' + cfg.label) : '');
    }

    function tagColorPairs() {
        return [
            ['#fff2f2', '#fe3d3d'],
            ['#e5ffd9', '#1f9f30'],
            ['#fbefff', '#bd37ff'],
            ['#effaff', '#37afff'],
            ['#f6f8fb', '#787d85']
        ];
    }

    function renderRegionField(field, product) {
        var value = currentFieldValue(field, product);
        var items = regionItems(value);
        var allow = field === 'kefa';
        var icon = allow ? '✓' : '✕';
        var preview = items.length
            ? items.map(function (item) {
                return '<span class="product-detail-region-chip ' + (allow ? 'allow' : 'block') + '"><span class="icon">' + icon + '</span><span>' + esc(item) + '</span></span>';
            }).join('')
            : '<span class="product-detail-empty">' + esc(fieldPlaceholder(field, configs[field] || {})) + '</span>';
        return '<div class="product-detail-region-editor js-region-editor" data-field="' + esc(field) + '">' +
            '<div class="product-detail-region-preview js-region-preview">' + preview + '</div>' +
            '<textarea class="product-detail-inline-textarea product-detail-region-textarea js-detail-inline-textarea js-region-textarea" data-field="' + esc(field) + '" placeholder="' + esc(fieldPlaceholder(field, configs[field] || {})) + '">' + esc(value) + '</textarea>' +
            '</div>';
    }

    function renderTagField(product) {
        var value = currentFieldValue('tags', product);
        var items = tagItems(value);
        var colors = tagColorPairs();
        var preview = items.length
            ? items.map(function (item, index) {
                var pair = colors[index % colors.length];
                return '<span class="product-detail-tag-chip" style="background:' + pair[0] + ';color:' + pair[1] + ';">' + esc(item) + '</span>';
            }).join('')
            : '<span class="product-detail-empty">' + esc(fieldPlaceholder('tags', configs.tags || {})) + '</span>';
        return '<div class="product-detail-tag-editor js-tag-editor" data-field="tags">' +
            '<div class="product-detail-tag-preview js-tag-preview">' + preview + '</div>' +
            '<input type="text" class="product-detail-inline-input product-detail-tag-input js-detail-inline-input js-tag-input" data-field="tags" value="' + esc(value) + '" placeholder="' + esc(fieldPlaceholder('tags', configs.tags || {})) + '">' +
            '</div>';
    }

    function renderGroupField(product) {
        var ids = parseVisibleGroupIds(currentFieldValue('visible_group_ids', product));
        var checkedMap = {};
        ids.forEach(function (id) { checkedMap[id] = true; });
        var html = '<div class="product-detail-group-editor js-visible-group-editor" data-field="visible_group_ids">' +
            '<label class="product-detail-group-option"><input type="checkbox" class="js-visible-group-all" value="" ' + (ids.length ? '' : 'checked') + '>全部分组</label>';
        agentGroupOptions().forEach(function (group) {
            var id = String(group.id || '');
            if (!id) return;
            html += '<label class="product-detail-group-option"><input type="checkbox" class="js-visible-group-item" value="' + esc(id) + '" ' + (checkedMap[id] ? 'checked' : '') + '>' + esc(group.name || ('分组#' + id)) + '</label>';
        });
        html += '<div class="product-detail-group-note">勾选具体分组后，仅这些分组的代理可看到该产品；全部分组表示不限制。</div></div>';
        return html;
    }

    function collectVisibleGroupValue($editor) {
        if (!$editor || !$editor.length || $editor.find('.js-visible-group-all').prop('checked')) {
            return '';
        }
        var ids = [];
        $editor.find('.js-visible-group-item:checked').each(function () {
            ids.push(layui.jquery(this).val());
        });
        return normalizeVisibleGroupValue(ids.join(','));
    }

    function renderBasicField(field, product) {
        var cfg = configs[field] || {label: field, editor: 'text'};
        var value = currentFieldValue(field, product);
        if (cfg.editor === 'readonly') return renderReadonlyValue(field, product);
        if (cfg.editor === 'custom_fields') return renderCustomFieldsBuilder(product);
        if (cfg.editor === 'image' || cfg.editor === 'images') return renderImageField(field, product);
        if (cfg.editor === 'channel') return '<select id="productDetailChannelSelect" class="product-detail-channel-select"><option value="">加载渠道商...</option></select><input id="productDetailChannelCascader" placeholder="请选择渠道商" class="layui-hide" />';
        if (cfg.editor === 'groups') return renderGroupField(product);
        if (field === 'kefa' || field === 'jinfa') return renderRegionField(field, product);
        if (field === 'tags') return renderTagField(product);
        if (field === 'number' && isSelfChannel(product)) {
            return '<div class="product-detail-inline-note">无需输入</div>';
        }
        if (cfg.editor === 'select') return renderInlineSelect(field, cfg, product);
        if (cfg.editor === 'textarea') return '<textarea class="product-detail-inline-textarea js-detail-inline-textarea" data-field="' + esc(field) + '" placeholder="' + esc(fieldPlaceholder(field, cfg)) + '">' + esc(value) + '</textarea>';
        if (cfg.editor === 'upload_text') {
            return '<div class="product-detail-upload-wrap">' +
                '<input type="text" class="product-detail-inline-input js-detail-inline-input" data-field="' + esc(field) + '" value="' + esc(value) + '" placeholder="' + esc(fieldPlaceholder(field, cfg)) + '">' +
                '<i id="productDetailFourPhotoUploadBtn" class="layui-icon layui-icon-picture product-detail-upload-btn" title="上传图片"></i>' +
            '</div>';
        }
        return '<input type="' + (cfg.editor === 'number' ? 'number' : 'text') + '" class="product-detail-inline-input js-detail-inline-input" data-field="' + esc(field) + '" value="' + esc(value) + '" placeholder="' + esc(fieldPlaceholder(field, cfg)) + '">';
    }

    function shouldHideField(field, product) {
        if ((field === 'four_photo_title' || field === 'four_photo') && String(product.is_four_photo || '0') !== '1') {
            return true;
        }
        return false;
    }

    function cell(field, product, colspan) {
        var cfg = configs[field] || {label: field, editor: 'text'};
        var content = renderBasicField(field, product);
        var editable = cfg.editor !== 'readonly' && cfg.editor !== 'custom_fields' && !(field === 'number' && isSelfChannel(product));
        return '<td class="product-detail-label">' + esc(cfg.label) + '</td><td class="product-detail-value' + (editable ? ' product-detail-editable' : '') + '" data-field="' + esc(field) + '"' + (colspan > 1 ? ' colspan="' + colspan + '"' : '') + '>' + content + '</td>';
    }

    function sectionHtml(section, product) {
        if (section.type === 'single_input' && section.field) {
            return '<div class="product-detail-section"><div class="product-detail-section-title"><span class="product-detail-section-title-text">' + esc(section.title) + '</span></div>' +
                '<div style="border:1px solid #f0f0f0;border-top:0;padding:12px 18px;">' + renderBasicField(section.field, product) + '</div></div>';
        }
        if (section.type === 'custom_fields' && section.field) {
            return '<div class="product-detail-section"><div class="product-detail-section-title"><span class="product-detail-section-title-text">' + esc(section.title) + '</span><span class="product-detail-section-title-tip">自定义表单内容，会显示在前台下单页“地址”下方</span></div>' +
                '<div style="border:1px solid #f0f0f0;border-top:0;padding:12px 18px;">' + renderBasicField(section.field, product) + '</div></div>';
        }
        return '<div class="product-detail-section"><div class="product-detail-section-title"><span class="product-detail-section-title-text">' + esc(section.title) + '</span></div><table class="product-detail-table"><tbody>' +
            section.rows.map(function (row) {
                var visibleFields = row.filter(function (field) { return !shouldHideField(field, product); });
                if (!visibleFields.length) return '';
                if (visibleFields.length === 1) return '<tr>' + cell(visibleFields[0], product, 3) + '</tr>';
                return '<tr>' + visibleFields.map(function (field) { return cell(field, product, 1); }).join('') + '</tr>';
            }).join('') +
            '</tbody></table></div>';
    }

    function buildChannelCascaderData() {
        channelState.meta = {};
        var data = [];
        var hasSelf = false;
        channelState.apiOptionList.forEach(function (api) {
            var displayText = api.display_name || api.name || '';
            var isSelf = ((api.name || '') === '自营') || displayText.indexOf('自营') === 0;
            if (isSelf) {
                hasSelf = true;
                return;
            }
            var configId = parseInt(api.api_config_id || 0, 10);
            var val = 'api::' + configId + '::' + displayText;
            channelState.meta[val] = {type: 'api', api_name: displayText, api_config_id: configId};
            data.push({value: val, label: displayText});
        });
        if (hasSelf && channelState.selfChannelList.length > 0) {
            var children = [];
            channelState.selfChannelList.forEach(function (item) {
                var name = (item.name || '').trim();
                var idStr = String(item.id || '');
                if (!idStr || !name) return;
                var childVal = 'self::' + idStr;
                channelState.meta[childVal] = {type: 'self', self_channel_id: idStr, self_channel_name: name};
                children.push({value: childVal, label: name});
            });
            if (children.length) {
                data.unshift({value: 'self_root', label: '自营', children: children});
            }
        }
        return data;
    }

    function findSelfPath(selfChannelId, data) {
        for (var i = 0; i < data.length; i++) {
            var root = data[i];
            var children = root.children || [];
            for (var j = 0; j < children.length; j++) {
                if (String(children[j].value) === ('self::' + selfChannelId)) {
                    return [root.value, children[j].value];
                }
            }
        }
        return [];
    }

    function findApiPath(apiName, apiConfigId, data) {
        for (var i = 0; i < data.length; i++) {
            var root = data[i];
            var meta = channelState.meta[root.value];
            if (!meta || meta.type !== 'api') continue;
            if (apiConfigId > 0) {
                if (parseInt(meta.api_config_id || 0, 10) === parseInt(apiConfigId, 10)) return [root.value];
            } else if (meta.api_name === apiName) {
                return [root.value];
            }
        }
        return [];
    }

    function flattenChannelOptions(data) {
        var options = [];
        (data || []).forEach(function (item) {
            if (item.children && item.children.length) {
                item.children.forEach(function (child) {
                    options.push({value: child.value, label: item.label + '/' + child.label});
                });
            } else {
                options.push({value: item.value, label: item.label});
            }
        });
        return options;
    }

    function currentChannelValue(data) {
        if (!state || !state.data) return '';
        var currentApiName = state.data.api_name_display || state.data.api_name || '';
        var currentApiConfigId = parseInt(state.data.api_config_id || 0, 10);
        var currentSelfChannelId = String(state.data.self_channel_id || 0);
        if (currentApiName.indexOf('自营') === 0 && currentSelfChannelId !== '0') {
            var selfPath = findSelfPath(currentSelfChannelId, data);
            if (selfPath.length) return selfPath[selfPath.length - 1];
        }
        var apiPath = findApiPath(currentApiName, currentApiConfigId, data);
        return apiPath.length ? apiPath[apiPath.length - 1] : '';
    }

    function applyChannelMeta(meta) {
        if (!meta || !state || !state.data) return;
        if (state.mode === 'create') {
            if (meta.type === 'self') {
                state.data.api_name = '自营';
                state.data.api_name_display = '自营/' + meta.self_channel_name;
                state.data.api_config_id = 0;
                state.data.self_channel_id = String(meta.self_channel_id || 0);
                state.data.number = '';
            } else {
                state.data.api_name = meta.api_name;
                state.data.api_name_display = meta.api_name;
                state.data.api_config_id = parseInt(meta.api_config_id || 0, 10);
                state.data.self_channel_id = 0;
            }
            updateBasicPanel();
        } else {
            var nextValue = meta.type === 'self' ? ('自营/' + meta.self_channel_name) : meta.api_name;
            save('api_name', nextValue, null, {
                api_config_id: meta.type === 'self' ? 0 : parseInt(meta.api_config_id || 0, 10),
                self_channel_id: meta.type === 'self' ? parseInt(meta.self_channel_id || 0, 10) : 0
            });
        }
    }

    function renderChannelSelect(data) {
        if (!state || !state.root) return;
        var $select = state.root.find('#productDetailChannelSelect');
        if (!$select.length) return;
        var options = flattenChannelOptions(data);
        var currentValue = currentChannelValue(data);
        var html = '<option value="">请选择渠道商</option>';
        options.forEach(function (item) {
            html += '<option value="' + esc(item.value) + '"' + (String(item.value) === String(currentValue) ? ' selected' : '') + '>' + esc(item.label) + '</option>';
        });
        $select.html(html);
    }

    function ensureChannelData(callback) {
        callback = callback || function () {};
        if (channelState.loaded) {
            callback();
            return;
        }
        channelState.callbacks.push(callback);
        if (channelState.loading) return;
        channelState.loading = true;

        var pending = 2;
        function done() {
            pending--;
            if (pending > 0) return;
            channelState.loading = false;
            channelState.loaded = true;
            var callbacks = channelState.callbacks.slice();
            channelState.callbacks = [];
            callbacks.forEach(function (cb) { cb(); });
        }

        layui.jquery.get('/admin/product/getApiOptions', function (res) {
            channelState.apiOptionList = (res && res.code === 1 && Array.isArray(res.data)) ? res.data : [];
            done();
        }, 'json').fail(function () {
            channelState.apiOptionList = [];
            done();
        });

        layui.jquery.get('/admin/product/getSelfChannelOptions', function (res) {
            channelState.selfChannelList = (res && res.code === 1 && Array.isArray(res.data)) ? res.data : [];
            done();
        }, 'json').fail(function () {
            channelState.selfChannelList = [];
            done();
        });
    }

    function initChannelCascader() {
        var cascader = window.layuiCascader;
        if (!state || !state.root || state.activeTab !== 'basic') return;
        var $elem = state.root.find('#productDetailChannelCascader');
        var $select = state.root.find('#productDetailChannelSelect');
        if (!$elem.length && !$select.length) return;
        ensureChannelData(function () {
            var data = buildChannelCascaderData();
            renderChannelSelect(data);
            if (!cascader || !$elem.length) return;
            channelState.cascaderIns = cascader.render({
                elem: '#productDetailChannelCascader',
                data: data,
                trigger: 'hover',
                clearable: false,
                renderFormat: function (labels) {
                    return labels.join('/');
                },
                onChange: function (values) {
                    var selected = values && values.length ? values[values.length - 1] : '';
                    var meta = channelState.meta[selected];
                    if (!meta) return;
                    applyChannelMeta(meta);
                }
            });
            var $wrap = $elem.closest('.product-detail-value');
            $wrap.find('.layui-cascader,.layui-form-select,.layui-unselect,.layui-input').css({
                border: '0',
                background: 'transparent',
                boxShadow: 'none',
                padding: '0',
                lineHeight: '1.7',
                height: 'auto',
                minHeight: '0',
                margin: '0'
            });
            var currentApiName = state.data.api_name_display || state.data.api_name || '';
            var currentApiConfigId = parseInt(state.data.api_config_id || 0, 10);
            var currentSelfChannelId = String(state.data.self_channel_id || 0);
            if (currentApiName.indexOf('自营') === 0 && currentSelfChannelId !== '0') {
                var selfPath = findSelfPath(currentSelfChannelId, data);
                if (selfPath.length && channelState.cascaderIns) {
                    channelState.cascaderIns.setValue(selfPath.join(','));
                    return;
                }
            }
            var apiPath = findApiPath(currentApiName, currentApiConfigId, data);
            if (apiPath.length && channelState.cascaderIns) {
                channelState.cascaderIns.setValue(apiPath.join(','));
            }
        });
    }

    function chooseImageFiles(options, callback) {
        var input = document.createElement('input');
        input.type = 'file';
        input.accept = 'image/*';
        input.multiple = !!(options && options.multiple);
        input.style.display = 'none';
        document.body.appendChild(input);
        input.addEventListener('change', function () {
            var files = Array.prototype.slice.call(input.files || []);
            document.body.removeChild(input);
            if (!files.length) return;
            callback(files);
        }, {once: true});
        input.click();
    }

    function uploadImageFiles(files, path, done) {
        var list = Array.isArray(files) ? files.slice() : [];
        if (!list.length) return done([]);
        var urls = [];
        var loading = layer().load(2, {shade: [0.15, '#000']});
        function next() {
            var file = list.shift();
            if (!file) {
                layer().close(loading);
                done(urls);
                return;
            }
            var formData = new FormData();
            formData.append('file', file, file.name || ('image_' + Date.now() + '.png'));
            formData.append('path', path);
            layui.jquery.ajax({
                url: '/common/Upload/single',
                type: 'POST',
                data: formData,
                processData: false,
                contentType: false,
                dataType: 'json',
                success: function (res) {
                    if (res && res.code === 1 && res.data && res.data.url) {
                        urls.push(res.data.url);
                        next();
                    } else {
                        layer().close(loading);
                        layer().msg((res && res.msg) ? res.msg : '上传失败', {icon: 2});
                    }
                },
                error: function () {
                    layer().close(loading);
                    layer().msg('上传失败，请重试', {icon: 2});
                }
            });
        }
        next();
    }

    function initImageUploads() {
    }

    function initBasicInteractive() {
        if (!state || !state.root || state.activeTab !== 'basic') return;
        initChannelCascader();
        initImageUploads();
        bindCustomFieldsBuilder();
    }

    function bindCustomFieldsBuilder() {
        if (!state || !state.root || state.activeTab !== 'basic') return;
    }

    function addCustomField() {
        if (!state || !state.data) return;
        var fields = parseCustomFields(state.data.product_custom_fields || '');
        fields.push(normalizeCustomFieldItem({
            label: '新字段' + (fields.length + 1),
            name: generateCustomFieldName(fields),
            type: 'text',
            required: '0',
            placeholder: ''
        }));
        var nextValue = stringifyCustomFields(fields);
        state.data.product_custom_fields = nextValue;
        updateBasicPanel();
    }

    function removeCustomField(index) {
        if (!state || !state.data) return;
        index = parseInt(index, 10);
        var fields = parseCustomFields(state.data.product_custom_fields || '');
        if (index < 0 || index >= fields.length) return;
        fields.splice(index, 1);
        var nextValue = stringifyCustomFields(fields);
        state.data.product_custom_fields = nextValue;
        updateBasicPanel();
    }

    function moveCustomField(index, direction) {
        if (!state || !state.data) return;
        index = parseInt(index, 10);
        direction = parseInt(direction, 10);
        var targetIndex = index + direction;
        var fields = parseCustomFields(state.data.product_custom_fields || '');
        if (index < 0 || index >= fields.length || targetIndex < 0 || targetIndex >= fields.length) return;
        var current = fields[index];
        fields[index] = fields[targetIndex];
        fields[targetIndex] = current;
        var nextValue = stringifyCustomFields(fields);
        state.data.product_custom_fields = nextValue;
        updateBasicPanel();
    }

    function updateCustomField(index, key, value) {
        if (!state || !state.data) return;
        index = parseInt(index, 10);
        var fields = parseCustomFields(state.data.product_custom_fields || '');
        if (index < 0 || index >= fields.length) return;
        if (key === 'options') {
            fields[index][key] = String(value || '').split(/\r\n|\r|\n/).map(function (item) {
                return item.trim();
            }).filter(Boolean);
        } else {
            fields[index][key] = value;
        }
        fields[index] = normalizeCustomFieldItem(fields[index]);
        var nextValue = stringifyCustomFields(fields);
        state.data.product_custom_fields = nextValue;
        updateBasicPanel();
    }

    function saveCustomFields() {
        if (!state || !state.data) return;
        if (state.mode === 'create') {
            layer().msg('自定义字段已暂存，点击底部“保存商品”后生效', {icon: 1, time: 1500});
            return;
        }
        var value = stringifyCustomFields(parseCustomFields(state.data.product_custom_fields || ''));
        var loading = layer().load(2, {shade: [0.15, '#000']});
        layui.jquery.post('/admin/product/quickUpdateField', {
            id: state.data.id,
            field: 'product_custom_fields',
            value: value
        }, function (res) {
            layer().close(loading);
            if (res.code !== 1 || !res.data) {
                layer().msg(res.msg || '保存失败', {icon: 2});
                return;
            }
            state.data = res.data;
            updateBasicPanel();
            layer().msg('保存成功', {icon: 1, time: 1200});
            if (window.reloadProductView) window.reloadProductView();
        }, 'json').fail(function () {
            layer().close(loading);
            layer().msg('网络错误，保存失败', {icon: 2});
        });
    }

    function collapseImageEditors() {
        if (!state || !state.root) return;
        state.root.find('.product-detail-image-edit-input').removeClass('active');
    }

    function activateCellEditor($cell) {
        if (!$cell || !$cell.length) return;
        var $field = $cell.find('.js-detail-inline-input:visible, .js-detail-inline-textarea:visible, .js-detail-inline-select:visible').first();
        if ($field.length) {
            $field.trigger('focus');
            if ($field.is('input, textarea') && $field[0] && $field[0].select) {
                $field[0].select();
            }
            if ($field.is('select')) {
                $field.trigger('click');
            }
            return;
        }

        var $regionPreview = $cell.find('.js-region-preview').first();
        if ($regionPreview.length) {
            $regionPreview.trigger('click');
            return;
        }

        var $tagPreview = $cell.find('.js-tag-preview').first();
        if ($tagPreview.length) {
            $tagPreview.trigger('click');
            return;
        }

        var $imageEditor = $cell.find('.product-detail-image-edit-input').first();
        if ($imageEditor.length) {
            $cell.find('.product-detail-image-edit-input').addClass('active');
            $imageEditor.trigger('focus');
            if ($imageEditor[0] && $imageEditor[0].select) {
                $imageEditor[0].select();
            }
            return;
        }

        var $cascaderInput = $cell.find('.layui-cascader input:visible, .layui-form-select .layui-input:visible').first();
        if ($cascaderInput.length) {
            $cascaderInput.trigger('click');
        }
    }

    function headerHtml(product) {
        var img = product.display_image || product.product_image_display || product.product_image || '/assets/images/card-default.jpg';
        var badges = [];
        if (state.mode === 'edit') badges.push(badge('产品ID:' + (product.id || ''), 'gray'));
        badges.push(badge(String(product.status) === '1' ? '在售' : '下架', String(product.status) === '1' ? 'allow' : 'gray', false));
        badges.push(badge(String(product.js_type) === '1' ? '秒返' : '次月返', String(product.js_type) === '1' ? 'block' : 'info', false));
        if (String(product.selectNumber) === '1') badges.push(badge('可选号', 'info'));
        if (String(product.iccid_auto_push) === '1') badges.push(badge('ICCID推送', 'allow'));
        if (String(product.isHot) === '1') badges.push(badge('热门', 'hot'));
        if (String(product.is_recommend) === '1') badges.push(badge('推荐', 'info'));
        if (parseVisibleGroupIds(product.visible_group_ids || '').length) badges.push(badge(formatVisibleGroupText(product.visible_group_ids), 'info', false));
        return '<div class="product-detail-header"><div><img class="product-detail-cover js-product-detail-preview" data-images="' + esc(JSON.stringify([img])) + '" src="' + esc(img) + '" alt="产品图片"></div>' +
            '<div class="product-detail-meta"><h2 class="product-detail-name">' + esc(product.name || '') + '</h2><div class="product-detail-package">' + esc(packageText(product)) + '</div><div class="product-detail-tags"><div class="product-detail-tags-left">' + badges.join('') + '</div></div><div class="product-detail-times"><span>创建时间: ' + esc(product.create_time || '') + '</span><span>更新时间: ' + esc(product.update_time || '') + '</span></div></div></div>';
    }

    function tabsHtml() {
        return '<div class="product-detail-tabs">' + detailTabs.map(function (tab) {
            return '<button type="button" class="product-detail-tab-btn' + (state.activeTab === tab.key ? ' active' : '') + '" data-tab="' + esc(tab.key) + '">' + esc(tab.label) + '</button>';
        }).join('') + '</div>';
    }

    function embeddedTabUrl(tabKey) {
        if (state.mode === 'create') {
            return '/admin/product/add?detail_embedded=1&detail_tab=' + encodeURIComponent(tabKey);
        }
        return '/admin/product/edit?id=' + encodeURIComponent(state.data.id) + '&detail_embedded=1&detail_tab=' + encodeURIComponent(tabKey);
    }

    function getEmbeddedFrameHeight(tabKey) {
        if (!state) return 520;
        var heights = state.embeddedHeights || {};
        var height = parseInt(heights[tabKey], 10) || 0;
        return Math.max(520, Math.min(height || 520, 1400));
    }

    function renderEmbeddedTabFrame(tabKey) {
        return '<iframe class="product-detail-tab-frame is-loading" data-tab="' + esc(tabKey) + '" src="' + esc(embeddedTabUrl(tabKey)) + '" frameborder="0" style="height:' + getEmbeddedFrameHeight(tabKey) + 'px;"></iframe>';
    }

    function renderBasicPanel(product) {
        return (state.mode === 'create' ? '' : headerHtml(product)) +
            basicSections.map(function (section) { return sectionHtml(section, product); }).join('');
    }

    function renderPanels(product) {
        return detailTabs.map(function (tab) {
            var active = state.activeTab === tab.key ? ' active' : '';
            if (tab.key === 'basic') {
                return '<div class="product-detail-tab-panel' + active + '" data-tab-panel="' + esc(tab.key) + '">' + renderBasicPanel(product) + '</div>';
            }
            var loaded = state.loadedTabs && state.loadedTabs[tab.key];
            return '<div class="product-detail-tab-panel' + active + '" data-tab-panel="' + esc(tab.key) + '" style="min-height:' + getEmbeddedFrameHeight(tab.key) + 'px;">' +
                (loaded ? renderEmbeddedTabFrame(tab.key) : '') +
                '</div>';
        }).join('');
    }

    function renderCreateActions() {
        if (state.mode !== 'create') return '';
        return '<div class="product-detail-create-actions"><button type="button" class="layui-btn layui-btn-normal js-product-create-save">保存商品</button></div>';
    }

    function updateBasicPanel() {
        if (!state || !state.root) return;
        state.root.find('[data-tab-panel="basic"]').html(renderBasicPanel(state.data));
        initBasicInteractive();
    }

    function activateTab(tabKey) {
        if (!state || !state.root) return;
        state.activeTab = tabKey;
        state.root.find('.product-detail-tab-btn').removeClass('active');
        state.root.find('.product-detail-tab-btn[data-tab="' + tabKey + '"]').addClass('active');
        state.root.find('.product-detail-tab-panel').removeClass('active');
        var $panel = state.root.find('[data-tab-panel="' + tabKey + '"]');
        if (tabKey !== 'basic') {
            $panel.css('min-height', getEmbeddedFrameHeight(tabKey) + 'px');
        }
        if (tabKey !== 'basic' && (!state.loadedTabs || !state.loadedTabs[tabKey])) {
            state.loadedTabs = state.loadedTabs || {};
            state.loadedTabs[tabKey] = true;
            $panel.html(renderEmbeddedTabFrame(tabKey));
        }
        $panel.addClass('active');
        if (tabKey === 'basic') {
            initBasicInteractive();
        }
    }

    function render() {
        if (!state || !state.root) return;
        state.root.html(tabsHtml() + renderPanels(state.data) + renderCreateActions());
        activateTab(state.activeTab || 'basic');
        initBasicInteractive();
    }

    function editValue(field) {
        if (!state || !state.data) return;
        var cfg = configs[field];
        if (!cfg || cfg.editor === 'readonly') return;
        var current = field === 'api_name' ? (state.data.api_name_display || state.data.api_name || '自营') : (field === 'detail_images' ? normalizeImages(state.data).join('\n') : (state.data[field] == null ? '' : String(state.data[field])));
        if (cfg.editor === 'select') {
            var html = '<div style="padding:16px;"><select id="productDetailSelectEditor" style="width:100%;height:38px;border:1px solid #d9d9d9;border-radius:4px;padding:0 10px;">' +
                (cfg.options || []).map(function (item) {
                    var selected = String(item[0]) === String(current) ? ' selected' : '';
                    return '<option value="' + esc(item[0]) + '"' + selected + '>' + esc(item[1]) + '</option>';
                }).join('') +
                '</select></div>';
            layer().open({
                type: 1,
                title: '编辑' + cfg.label,
                area: ['420px', '180px'],
                shade: 0.2,
                content: html,
                btn: ['保存', '取消'],
                yes: function (index) { save(field, $('#productDetailSelectEditor').val(), index); }
            });
            return;
        }
        layer().prompt({
            title: '编辑' + cfg.label,
            value: current,
            formType: cfg.editor === 'textarea' ? 2 : 0,
            area: cfg.editor === 'textarea' ? ['720px', '360px'] : ['420px', '180px']
        }, function (value, index) {
            save(field, value, index);
        });
    }

    function save(field, value, editorIndex, extraPayload) {
        if (!state || !state.data) return;
        if (field === 'product_custom_fields') {
            value = stringifyCustomFields(parseCustomFields(value));
        }
        if (field === 'visible_group_ids') {
            value = normalizeVisibleGroupValue(value);
        }
        if (state.mode === 'create') {
            if (field === 'tags') value = normalizeTagValue(value);
            state.data[field] = value;
            if (field === 'card_type' && String(value) === '0') {
                state.data.card_price = '0';
            }
            if (field === 'is_four_photo' && String(value) !== '1') {
                state.data.four_photo_title = '';
                state.data.four_photo = '';
            }
            if (field === 'detail_images') {
                state.data.detail_images_display = normalizeImages(state.data);
            }
            updateBasicPanel();
            return;
        }
        if (field === 'tags') {
            value = normalizeTagValue(value);
        }
        if (field === 'number' && isSelfChannel(state.data)) return;
        var current = currentFieldValue(field, state.data);
        if (String(value == null ? '' : value) === String(current == null ? '' : current)) {
            if (typeof editorIndex === 'number') layer().close(editorIndex);
            return;
        }
        var loading = layer().load(2, {shade: [0.15, '#000']});
        var payload = {
            id: state.data.id,
            field: field,
            value: value
        };
        if (extraPayload) {
            for (var extraKey in extraPayload) {
                if (Object.prototype.hasOwnProperty.call(extraPayload, extraKey)) {
                    payload[extraKey] = extraPayload[extraKey];
                }
            }
        }
        layui.jquery.post('/admin/product/quickUpdateField', payload, function (res) {
            layer().close(loading);
            if (res.code !== 1 || !res.data) {
                layer().msg(res.msg || '保存失败', {icon: 2});
                return;
            }
            if (typeof editorIndex === 'number') layer().close(editorIndex);
            state.data = res.data;
            updateBasicPanel();
            layer().msg('保存成功', {icon: 1, time: 1000});
            if (window.reloadProductView) window.reloadProductView();
        }, 'json').fail(function () {
            layer().close(loading);
            layer().msg('网络错误，保存失败', {icon: 2});
        });
    }

    function refreshCurrentProduct(silent) {
        if (!state || !state.data || !state.data.id) return;
        layui.jquery.get('/admin/product/detail', {id: state.data.id}, function (res) {
            if (res.code !== 0 || !res.data) {
                if (!silent) layer().msg(res.msg || '刷新失败', {icon: 2});
                return;
            }
            state.data = res.data;
            updateBasicPanel();
            if (window.reloadProductView) window.reloadProductView();
            if (!silent) layer().msg('刷新成功', {icon: 1, time: 1000});
        }, 'json');
    }

    function collectEmbeddedCreatePayload() {
        var payload = {};
        if (!state || !state.root) return payload;
        state.root.find('.product-detail-tab-frame').each(function () {
            var frame = this;
            try {
                if (frame.contentWindow && typeof frame.contentWindow.__getEmbeddedCreatePayload === 'function') {
                    var part = frame.contentWindow.__getEmbeddedCreatePayload() || {};
                    for (var key in part) {
                        if (Object.prototype.hasOwnProperty.call(part, key)) {
                            payload[key] = part[key];
                        }
                    }
                }
            } catch (e) {
            }
        });
        return payload;
    }

    function buildCreatePayload() {
        var data = state.data || createEmptyProduct();
        var payload = {
            name: data.name || '',
            yys: data.yys || '',
            api_name: data.api_name_display || data.api_name || '',
            api_config_id: data.api_config_id || 0,
            self_channel_id: data.self_channel_id || 0,
            number: isSelfChannel(data) ? '' : (data.number || ''),
            yuezu: data.yuezu || '',
            commission: data.commission || 0,
            js_type: data.js_type || '1',
            status: data.status || '1',
            is_open: data.is_open || '1',
            flow: data.flow || 0,
            dingxiang: data.dingxiang || 0,
            call: data.call || 0,
            sms: data.sms || 0,
            guishudi: data.guishudi || '全国',
            kefa: data.kefa || '',
            jinfa: data.jinfa || '',
            peisong: data.peisong || '包邮',
            kaika: data.kaika || '线上激活',
            age: data.age || '18-65岁',
            heyue: data.heyue || '无合约',
            selectNumber: data.selectNumber || '0',
            iccid_auto_push: data.iccid_auto_push || '0',
            isHot: data.isHot || '0',
            is_recommend: data.is_recommend || '0',
            is_id_photo: data.is_id_photo || '0',
            is_four_photo: data.is_four_photo || '0',
            four_photo_title: data.four_photo_title || '',
            four_photo: data.four_photo || '',
            card_type: data.card_type || '0',
            card_price: data.card_price || 0,
            first_chongzhi: data.first_chongzhi || '',
            rule: data.rule || '',
            js_require: data.js_require || '',
            mark: data.mark || '',
            tags: normalizeTagValue(data.tags || ''),
            visible_group_ids: normalizeVisibleGroupValue(data.visible_group_ids || ''),
            product_image: data.product_image || '',
            detail_images: data.detail_images || '',
            external_order_url: data.external_order_url || '',
            product_custom_fields: stringifyCustomFields(parseCustomFields(data.product_custom_fields || '')),
            policy_order_security_check: data.policy_order_security_check || '',
            policy_shop_order_verify: data.policy_shop_order_verify || '',
            policy_shop_order_idcard_verify: data.policy_shop_order_idcard_verify || '',
            policy_product_ship_sms_notice: data.policy_product_ship_sms_notice || '',
            policy_order_review_failed_sms_notice: data.policy_order_review_failed_sms_notice || ''
        };
        if (payload.detail_images && String(payload.detail_images).charAt(0) !== '[') {
            var images = normalizeImages({detail_images: payload.detail_images});
            payload.detail_images = images.length ? JSON.stringify(images) : '';
        }
        var extra = collectEmbeddedCreatePayload();
        for (var key in extra) {
            if (Object.prototype.hasOwnProperty.call(extra, key)) {
                payload[key] = extra[key];
            }
        }
        return payload;
    }

    function submitCreateProduct() {
        if (!state || state.mode !== 'create') return;
        var payload = buildCreatePayload();
        if (!payload.name) {
            layer().msg('产品名称不能为空', {icon: 2});
            return;
        }
        if (!payload.yys) {
            layer().msg('请选择运营商', {icon: 2});
            return;
        }
        if (!payload.api_name) {
            layer().msg('请选择渠道商', {icon: 2});
            return;
        }
        if (String(payload.api_name).indexOf('自营') !== 0 && !String(payload.number || '').trim()) {
            layer().msg('请输入上游产品编码', {icon: 2});
            return;
        }
        var loading = layer().load(2, {shade: [0.15, '#000']});
        layui.jquery.post('/admin/product/add', payload, function (res) {
            layer().close(loading);
            if (res.code !== 1) {
                layer().msg(res.msg || '添加失败', {icon: 2});
                return;
            }
            layer().msg('添加成功', {icon: 1, time: 1000});
            if (window.reloadProductView) window.reloadProductView();
            if (state && typeof state.index === 'number') {
                layer().close(state.index);
            }
        }, 'json').fail(function () {
            layer().close(loading);
            layer().msg('网络错误，添加失败', {icon: 2});
        });
    }

    function updateEmbeddedHeight(tabKey, height) {
        if (!state || !state.root) return;
        var $frame = state.root.find('.product-detail-tab-frame[data-tab="' + tabKey + '"]');
        var $panel = state.root.find('[data-tab-panel="' + tabKey + '"]');
        if (!$frame.length) return;
        var nextHeight = parseInt(height, 10) || 0;
        if (nextHeight > 0) {
            nextHeight = Math.max(420, Math.min(nextHeight, 1400));
            state.embeddedHeights = state.embeddedHeights || {};
            var prevHeight = parseInt(state.embeddedHeights[tabKey], 10) || 0;
            state.embeddedHeights[tabKey] = nextHeight;
            if (Math.abs(($frame.height() || 0) - nextHeight) >= 4 || $frame.hasClass('is-loading')) {
                $frame.height(nextHeight);
            }
            if ($panel.length) {
                $panel.css('min-height', nextHeight + 'px');
            }
            if ($frame.hasClass('is-loading') || Math.abs(prevHeight - nextHeight) >= 8) {
                $frame.removeClass('is-loading');
            }
        }
    }

    function openPhotos(images, start) {
        var data = (images || []).map(function (img, idx) { return {src: img, alt: '产品图片' + (idx + 1)}; });
        if (!data.length) {
            layer().msg('暂无图片', {icon: 0});
            return;
        }
        layer().photos({photos: {data: data}, anim: 5, start: start || 0});
    }

    function open(data) {
        ensureStyles();
        getHostWindow().productDetailEditor = window.productDetailEditor;
        var productId = data && typeof data === 'object' ? data.id : data;
        if (!productId) {
            layer().msg('产品ID不能为空', {icon: 2});
            return;
        }
        var loading = layer().load(2, {shade: [0.2, '#000']});
        layui.jquery.get('/admin/product/detail', {id: productId}, function (res) {
            layer().close(loading);
            if (res.code !== 0 || !res.data) {
                layer().msg(res.msg || '获取产品详情失败', {icon: 2});
                return;
            }
            layer().open({
                type: 1,
                title: '产品详情',
                area: ['700px', '100%'],
                offset: 'r',
                anim: -1,
                shade: 0.3,
                shadeClose: true,
                content: '<div id="productDetailEditorRoot" class="product-detail-drawer"></div>',
                btn: false,
                closeBtn: 1,
                move: false,
                skin: 'layer-drawer-right',
                success: function (layero, index) {
                    lockDrawerScroll();
                    normalizeDrawerLayer(layero, index);
                    state = {index: index, layero: layero, root: layero.find('#productDetailEditorRoot'), data: res.data, activeTab: 'basic', mode: 'edit', loadedTabs: {}, embeddedHeights: {}};
                    render();
                    setTimeout(function () { layero.addClass('layer-drawer-show'); }, 50);
                },
                end: function () {
                    unlockDrawerScroll();
                    state = null;
                }
            });
        }, 'json').fail(function () {
            layer().close(loading);
            layer().msg('网络错误，获取产品详情失败', {icon: 2});
        });
    }

    function openCreate() {
        ensureStyles();
        getHostWindow().productDetailEditor = window.productDetailEditor;
        layer().open({
            type: 1,
            title: '添加商品',
            area: ['700px', '100%'],
            offset: 'r',
            anim: -1,
            shade: 0.3,
            shadeClose: true,
            content: '<div id="productDetailEditorRoot" class="product-detail-drawer"></div>',
            btn: false,
            closeBtn: 1,
            move: false,
            skin: 'layer-drawer-right',
            success: function (layero, index) {
                lockDrawerScroll();
                normalizeDrawerLayer(layero, index);
                state = {index: index, layero: layero, root: layero.find('#productDetailEditorRoot'), data: createEmptyProduct(), activeTab: 'basic', mode: 'create', loadedTabs: {}, embeddedHeights: {}};
                render();
                setTimeout(function () { layero.addClass('layer-drawer-show'); }, 50);
            },
            end: function () {
                unlockDrawerScroll();
                state = null;
            }
        });
    }

    eventTargets()
        .off('click.productDetailPreview')
        .on('click.productDetailPreview', '.js-product-detail-preview', function (e) {
            e.preventDefault();
            e.stopPropagation();
            var images = [];
            var start = parseInt(layui.jquery(this).attr('data-start') || '0', 10);
            try { images = JSON.parse(layui.jquery(this).attr('data-images') || '[]'); } catch (err) {}
            openPhotos(images, start);
        })
        .off('click.productDetailMainUpload')
        .on('click.productDetailMainUpload', '#productDetailMainImageUploadBtn', function (e) {
            e.preventDefault();
            e.stopPropagation();
            e.stopImmediatePropagation();
            collapseImageEditors();
            chooseImageFiles({multiple: false}, function (files) {
                uploadImageFiles(files, 'products/main', function (urls) {
                    if (!urls.length) return;
                    state.root.find('.js-detail-inline-input[data-field="product_image"]').val(urls[0]);
                    save('product_image', urls[0]);
                });
            });
        })
        .off('click.productDetailDetailUpload')
        .on('click.productDetailDetailUpload', '#productDetailDetailImagesUploadBtn', function (e) {
            e.preventDefault();
            e.stopPropagation();
            e.stopImmediatePropagation();
            collapseImageEditors();
            chooseImageFiles({multiple: true}, function (files) {
                uploadImageFiles(files, 'products/detail', function (urls) {
                    if (!urls.length) return;
                    var $textarea = state.root.find('.js-detail-inline-textarea[data-field="detail_images"]');
                    var current = ($textarea.val() || '').trim();
                    var nextValue = current ? (current + '\n' + urls.join('\n')) : urls.join('\n');
                    $textarea.val(nextValue);
                    save('detail_images', nextValue);
                });
            });
        })
        .off('click.productDetailDetailReplace')
        .on('click.productDetailDetailReplace', '.js-detail-image-replace', function (e) {
            e.preventDefault();
            e.stopPropagation();
            e.stopImmediatePropagation();
            collapseImageEditors();
            var index = parseInt(layui.jquery(this).attr('data-index') || '0', 10);
            chooseImageFiles({multiple: false}, function (files) {
                uploadImageFiles(files, 'products/detail', function (urls) {
                    if (!urls.length) return;
                    var images = normalizeImages(state.data);
                    images[index] = urls[0];
                    var nextValue = images.join('\n');
                    state.root.find('.js-detail-inline-textarea[data-field="detail_images"]').val(nextValue);
                    save('detail_images', nextValue);
                });
            });
        })
        .off('click.productDetailFourPhotoUpload')
        .on('click.productDetailFourPhotoUpload', '#productDetailFourPhotoUploadBtn', function (e) {
            e.preventDefault();
            e.stopPropagation();
            e.stopImmediatePropagation();
            chooseImageFiles({multiple: false}, function (files) {
                uploadImageFiles(files, 'products/four_photo', function (urls) {
                    if (!urls.length) return;
                    state.root.find('.js-detail-inline-input[data-field="four_photo"]').val(urls[0]);
                    save('four_photo', urls[0]);
                });
            });
        })
        .off('click.productDetailMainImageDelete')
        .on('click.productDetailMainImageDelete', '.js-main-image-delete', function (e) {
            e.preventDefault();
            e.stopPropagation();
            save('product_image', '');
        })
        .off('click.productDetailDetailImageDelete')
        .on('click.productDetailDetailImageDelete', '.js-detail-image-delete', function (e) {
            e.preventDefault();
            e.stopPropagation();
            var index = parseInt(layui.jquery(this).attr('data-index') || '0', 10);
            var images = normalizeImages(state.data);
            images.splice(index, 1);
            save('detail_images', images.join('\n'));
        })
        .off('click.productDetailTab')
        .on('click.productDetailTab', '.product-detail-tab-btn', function (e) {
            e.preventDefault();
            if (!state) return;
            activateTab(layui.jquery(this).data('tab') || 'basic');
        })
        .off('click.productDetailCellActivate')
        .on('click.productDetailCellActivate', '.product-detail-editable', function (e) {
            var $target = layui.jquery(e.target);
            if ($target.closest('.js-product-detail-preview,.js-detail-inline-input,.js-detail-inline-textarea,.js-detail-inline-select,.layui-cascader,.layui-form-select,.product-detail-upload-btn,.product-detail-upload-link,.product-detail-image-action,.product-detail-image-upload,.product-custom-field-add,.product-custom-field-remove,.product-custom-field-input,.product-custom-field-select,.product-custom-field-textarea,.js-visible-group-editor').length) {
                return;
            }
            activateCellEditor(layui.jquery(this));
        })
        .off('change.productDetailVisibleGroups')
        .on('change.productDetailVisibleGroups', '.js-visible-group-all, .js-visible-group-item', function () {
            var $input = layui.jquery(this);
            var $editor = $input.closest('.js-visible-group-editor');
            if ($input.hasClass('js-visible-group-all')) {
                if ($input.prop('checked')) {
                    $editor.find('.js-visible-group-item').prop('checked', false);
                } else if (!$editor.find('.js-visible-group-item:checked').length) {
                    $input.prop('checked', true);
                }
            } else {
                if ($editor.find('.js-visible-group-item:checked').length) {
                    $editor.find('.js-visible-group-all').prop('checked', false);
                } else {
                    $editor.find('.js-visible-group-all').prop('checked', true);
                }
            }
            save('visible_group_ids', collectVisibleGroupValue($editor));
        })
        .off('change.productDetailInlineSelect')
        .on('change.productDetailInlineSelect', '.js-detail-inline-select', function () {
            save(layui.jquery(this).data('field'), layui.jquery(this).val());
        })
        .off('change.productDetailChannelSelect')
        .on('change.productDetailChannelSelect', '#productDetailChannelSelect', function () {
            var selected = layui.jquery(this).val();
            var meta = channelState.meta[selected];
            if (!meta) return;
            applyChannelMeta(meta);
        })
        .off('blur.productDetailInlineInput')
        .on('blur.productDetailInlineInput', '.js-detail-inline-input, .js-detail-inline-textarea', function () {
            var $input = layui.jquery(this);
            save($input.data('field'), $input.val());
            setTimeout(function () {
                if ($input.hasClass('product-detail-image-edit-input')) {
                    $input.removeClass('active');
                }
                if ($input.hasClass('js-region-textarea')) {
                    var $editor = $input.closest('.js-region-editor');
                    $input.hide();
                    $editor.find('.js-region-preview').show();
                }
                if ($input.hasClass('js-tag-input')) {
                    var $tagEditor = $input.closest('.js-tag-editor');
                    $input.hide();
                    $tagEditor.find('.js-tag-preview').show();
                }
            }, 120);
        })
        .off('click.productDetailRegionPreview')
        .on('click.productDetailRegionPreview', '.js-region-preview', function (e) {
            e.preventDefault();
            e.stopPropagation();
            var $editor = layui.jquery(this).closest('.js-region-editor');
            $editor.find('.js-region-preview').hide();
            var $textarea = $editor.find('.js-region-textarea');
            $textarea.show().trigger('focus');
            if ($textarea[0] && $textarea[0].select) {
                $textarea[0].select();
            }
        })
        .off('click.productDetailTagPreview')
        .on('click.productDetailTagPreview', '.js-tag-preview', function (e) {
            e.preventDefault();
            e.stopPropagation();
            var $editor = layui.jquery(this).closest('.js-tag-editor');
            $editor.find('.js-tag-preview').hide();
            var $input = $editor.find('.js-tag-input');
            $input.show().trigger('focus');
            if ($input[0] && $input[0].select) {
                $input[0].select();
            }
        })
        .off('keydown.productDetailInlineInput')
        .on('keydown.productDetailInlineInput', '.js-detail-inline-input', function (e) {
            if (e.which === 13) {
                e.preventDefault();
                layui.jquery(this).blur();
            }
        })
        .off('keydown.productDetailInlineTextarea')
        .on('keydown.productDetailInlineTextarea', '.js-detail-inline-textarea', function (e) {
            if ((e.ctrlKey || e.metaKey) && e.which === 13) {
                e.preventDefault();
                layui.jquery(this).blur();
            }
        })
        .off('click.productCreateSave')
        .on('click.productCreateSave', '.js-product-create-save', function (e) {
            e.preventDefault();
            submitCreateProduct();
        });

    window.productDetailEditor = {
        open: open,
        openCreate: openCreate,
        openPhotos: openPhotos,
        refreshCurrentProduct: refreshCurrentProduct,
        updateEmbeddedHeight: updateEmbeddedHeight,
        notifyEmbeddedSaved: function () {
            refreshCurrentProduct(true);
        }
    };
    window.productDetailEditorAddCustomField = addCustomField;
    window.productDetailEditorMoveCustomField = moveCustomField;
    window.productDetailEditorRemoveCustomField = removeCustomField;
    window.productDetailEditorSaveCustomFields = saveCustomFields;
    window.productDetailEditorUpdateCustomField = updateCustomField;
})();
