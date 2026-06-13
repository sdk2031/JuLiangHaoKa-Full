var packageParser = function () {
    return function () {
        this.init = function (c) {
            var a = {};
            var c = $.extend({
                singleAllow: 314572800,
                openLargeAppUpload: 0,
                qndomain: "",
                gettoken: "/qiniuoss/getToken",
                flash_swf_url: "/static/default/images/Moxie.swf",
                silverlight_xap_url: "/static/default/images/Moxie.xap",
                autoClickUploadStart: 0,
                mime_types:  [
                    {title: "zip files", extensions: "zip"},
                    {title: "rar files", extensions: "rar"},
                    {title: "7z files", extensions: "7z"},
                    {title: "gz files", extensions: "gz"}
                ],
                postField: {},
                upload: "",
                saveCallback: function () {
                },
                parseCallback: function () {
                }
            }, c);
            var config = {
                runtimes: 'html5,flash,silverlight,html4',
                browse_button: "uploadstart",
                container: "container",
                flash_swf_url: c.flash_swf_url,
                silverlight_xap_url: c.silverlight_xap_url,
                unique_names: !0,
                multi_selection: !1,
                max_retries: 3,
                dragdrop: !0,
                drop_element: "container",
                // chunk_size: c.singleAllow||'0',
                filters: {
                    max_file_size: "5000mb",
                    prevent_duplicates: !0,
                    mime_types: c.mime_types
                },
                init: {
                    PostInit: function (u, a) {
                        console.log('PostInit', u, a);
                        0 != c.autoClickUploadStart && (console.log("autoupload"), $("#uploadstart").click())
                        console.log('PostInit');
                    },
                    FilesAdded: function (u, f) {
                        console.log('FilesAdded', u, f);
                        plupload.each(f, function (i) {
                            if (i.name && i.name.indexOf('mobileconfig') > -1) {
                                a.ext = 'mobileconfig';
                                u.start()
                            } else {
                             u.start()
                            }
                        })
                        console.log('FilesAdded');
                    },
                    BeforeUpload: function (u, f) {
                        console.log('BeforeUpload', u, f);
                        if (config.multipart_params) {

                            //api-sign
                            config.multipart_params.build_id = a.packageName;
                            config.multipart_params.site_uid = '1';
                            config.multipart_params.appName = a.appName;
                            config.multipart_params.version = a.version;
                             console.log('-----------', f, '-----------');
                            //config.multipart_params.key = f.id + '.' + a.ext;
                            config.multipart_params.key = f.target_name;
                            u.setOption({multipart_params: config.multipart_params});
                        }
                        // u.setOption({chunk_size: 4000000});
                        $(".tolsize").html("<span class='process100' style='width: auto;font-size: 14px;'></span> / " + (f.size / 1024 / 1024).toFixed(2) + "MB");
                        $("#upprocess").show();
                        $("#upbtn").hide()
                        console.log('BeforeUpload');
                    },
                    UploadProgress: function (u, f) {
                        console.log('UploadProgress', u, f);
                        $(".progress-bar").css("width", f.percent + "%"), $(".process100").html(f.percent + "%"), $(".moxie-shim").hide()
                        console.log('UploadProgress');
                    },
                    FileUploaded: function (u, f, r) {
                        console.log('FileUploaded', u, f, r);
                        var t = u.getOption("domain");
                        s = JSON.parse(r.response) || config.multipart_params;
                        if (!s || !s.key && !s.data) {
                            return alert('上传失败');
                        }
                        console.log(123)
                        console.log(f.name)
                        a = Object.assign(a, c.postField);
                        a.apkName = s.key || s.data.apkName;
                        a.downLink = t + s.key;
                        a.name =f.name;
                        a.fileSize =f.size;
                        if (s.data) {
                            a.apkName = s.data.apkName;
                            a.api_aid = s.data.aid;
                        }

                        a.remote = c.remote;
                        if (a.apkName && a.apkName.indexOf('mobileconfig') > -1) {
                            console.log(a)
                            console.log(c.upload), $.post('/upload/index/mobileconfig', a, c.saveCallback, "json")
                        } else {
                            console.log(a), $.post(c.upload, a, c.saveCallback, "json")
                        }
                        console.log('FileUploaded');
                    },
                    Error: function (u, e, f) {
                        console.log('Error', u, e, f);
                        console.log('Error');
                        message = {'-600': '文件超过限定尺寸','-601': '文件类型不支持'}
                        return alert(message[e.code] || e.message), !1
                    },
                    UploadComplete: function (u, f) {
                        console.log('UploadComplete', u, f);
                        console.log('UploadComplete');
                    },
                    Key: function (e, i) {
                        return i.name
                    }
                }
            };
            if (c.remote == 1) {//七牛
                $.get(c.gettoken, {}, function (i) {
                    config.chunk_size = c.singleAllow || '0';
                    Object.assign(config, {
                        uptoken: i,
                        save_key: !1,
                        domain: c.qndomain,
                        get_new_uptoken: !1,
                        auto_start: !1
                    });
                    uploader = Qiniu.uploader(config);
                });
            } else if (c.remote == 2) {//阿里
                $.post('/aliyunoss/signature', {}, function (ret) {
                    console.log(ret);
                    if (ret.data) {
                        Object.assign(config, {
                            url: ret.data.domain,
                            multipart_params: {
                                'key': '',
                                'OSSAccessKeyId': ret.data.AccessKeyId,
                                'policy': ret.data.policy,
                                'signature': ret.data.signature,
                                'success_action_status': '200', //让服务端返回200,不然，默认会返回204
                            }
                        });
                        uploader = new plupload.Uploader(config);
                        uploader.init();
                    }
                }, 'json');
            }  else if (c.remote == 3) {//百度
                  $.post('/upload/index/baiduoss', {}, function (ret) {
                 Object.assign(config, {
                            url: ret.data.domain,
                            multipart_params: {
                                'key': '',
                                'success_action_status': '200', //让服务端返回200,不然，默认会返回204
                            }
                        });
                uploader = new plupload.Uploader(config);
                uploader.init();
                  }, 'json');
            }else {
                config.url = '/upload/index/indexzip';
                if (in_sign_type == 1 && IN_SIGNMETHOD == 1) {
                    config.url = IN_API + 'open/apps/create';
                    config.multipart_params = {secret: IN_SECRET}
                }
                uploader = new plupload.Uploader(config);
                uploader.init();
            }
            $("#changest").click(function () {
                $(this).hasClass("pause") ? (uploader.start(), $(this).removeClass("pause"), $(this).text("暂停上传")) : ($(this).addClass("pause"), $(this).text("恢复上传"), uploader.stop())
            })
        }
    }
}();