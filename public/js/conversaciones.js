(() => {
    const root = document.getElementById("conversaciones");
    if (!root) return;
    const $ = (id) => document.getElementById(id),
        base = root.dataset.base;
    let selected = null,
        page = 1,
        offset = 0,
        busy = false,
        loading = false;
    const node = (tag, text, cls) => {
        const e = document.createElement(tag);
        e.textContent = text ?? "";
        if (cls) e.className = cls;
        return e;
    };
    const error = (msg) => {
        $("chatError").textContent = msg || "";
        $("chatError").classList.toggle("d-none", !msg);
    };
    function uuid() {
        if (globalThis.crypto?.randomUUID) return globalThis.crypto.randomUUID();
        const b=globalThis.crypto.getRandomValues(new Uint8Array(16));b[6]=(b[6]&15)|64;b[8]=(b[8]&63)|128;
        const h=Array.from(b,x=>x.toString(16).padStart(2,'0')).join('');return [h.slice(0,8),h.slice(8,12),h.slice(12,16),h.slice(16,20),h.slice(20)].join('-');
    }
    async function api(path, data) {
        const r = await fetch(base + path, {
            method: data ? "POST" : "GET",
            credentials: "same-origin",
            headers: {
                Accept: "application/json",
                ...(data instanceof FormData
                    ? {}
                    : { "Content-Type": "application/json" }),
                "X-CSRF-TOKEN": document.querySelector(
                    'meta[name="csrf-token"]',
                ).content,
            },
            ...(data
                ? {
                      body:
                          data instanceof FormData
                              ? data
                              : JSON.stringify(data),
                  }
                : {}),
        });
        const d = await r.json().catch(() => ({}));
        if (!r.ok)
            throw Object.assign(
                new Error(
                    d.message ||
                        d.mensaje ||
                        Object.values(d.errors || {})[0]?.[0] ||
                        "No fue posible completar la operación.",
                ),
                { status: r.status },
            );
        return d;
    }
    async function history() {
        if (!selected) return;
        const id = selected.id;
        try {
            const d = await api("/" + id + "/mensajes?offset=" + offset);
            if (selected?.id !== id) return;
            $("chatAnteriores").disabled = offset + 100 >= d.total;
            $("chatRecientes").disabled = offset === 0;
            $("chatCliente").replaceChildren();
            if (d.cliente) {
                $("chatCliente").append(node("span", d.cliente.nombre));
                for (const k of ["ventas", "apartados", "cotizaciones"])
                    if (d.cliente[k]) {
                        const a = node(
                            "a",
                            k,
                            "btn btn-sm btn-outline-primary",
                        );
                        a.href = d.cliente[k];
                        a.target = "_blank";
                        a.rel = "noopener";
                        $("chatCliente").append(a);
                    }
            }
            const panel = $("chatMensajes"),
                bottom =
                    panel.scrollHeight - panel.scrollTop - panel.clientHeight <
                    80;
            panel.replaceChildren();
            [...d.messages].reverse().forEach((m) => {
                const box = node(
                    "div",
                    null,
                    "rounded p-2 mb-2 " +
                        (m.direction === "outgoing"
                            ? "bg-primary-subtle ms-4"
                            : "bg-light me-4"),
                );
                box.append(
                    node(
                        "small",
                        m.direction === "outgoing" ? "Agencia" : "Cliente",
                        "d-block text-muted",
                    ),
                    node("div", m.body || (m.type === "document" || m.attachment ? "" : "[" + m.type + "]")),
                );
                if (m.type === "document" || m.attachment) {
                    const card = node("div", null, "chat-attachment");
                    const filename = m.attachment?.filename || "Documento adjunto";
                    const isPdf = /\.pdf$/i.test(filename) || m.attachment?.mimetype === "application/pdf";
                    card.append(node("span", isPdf ? "PDF" : "ARCHIVO", "chat-attachment-icon"), node("strong", filename));
                    card.append(node("small", m.direction === "outgoing" ? "Documento enviado" : "Documento recibido", "d-block text-muted"));
                    box.prepend(card);
                }
                panel.append(box);
            });
            if (bottom) panel.scrollTop = panel.scrollHeight;
            $("chatEventos").replaceChildren(
                ...d.eventos.map((e) =>
                    node(
                        "p",
                        (e.name || "Sistema") +
                            " · " +
                            e.created_at +
                            " · " +
                            e.texto,
                        "small text-break",
                    ),
                ),
            );
        } catch (e) {
            if (selected?.id === id) {
                if (e.status === 403) {
                    selected = null;
                    $("chatPanel").classList.add("d-none");
                    $("chatMensajes").replaceChildren();
                    $("chatEventos").replaceChildren();
                }
                error(e.message);
            }
        }
    }
    function reflejarResponsable() {
        const disponible = selected?.estado === "en_atencion" && Boolean(selected?.assigned_to);
        for (const formId of ["chatRespuesta", "chatDocumento"]) {
            $(formId)?.querySelectorAll("button,textarea,input").forEach(el=>el.disabled=!disponible || busy);
        }
        const aviso=$("chatEnvioAviso");
        if(aviso){aviso.classList.toggle("d-none",disponible || !selected);aviso.textContent=selected?.assigned_to ? "La atención está cerrada. Reábrela para enviar mensajes o documentos." : "Asigna un responsable en Responsable y seguimiento para habilitar el envío de mensajes y documentos.";}
        const selector = $("chatUsuario");
        if (selector) {
            selector.value = selected?.assigned_to == null ? "" : String(selected.assigned_to);
            if (selector.selectedIndex < 0) selector.value = "";
            if (window.jQuery) window.jQuery(selector).trigger("change.select2");
        }
        $("chatResponsable").textContent = "Responsable: " + (selected?.responsable?.name || "Sin asignar");
    }
    let abrirDesdeNotificacion = new URLSearchParams(location.search).get('conversacion');
    async function load() {
        if (loading || busy) return;
        loading = true;
        try {
            const d = await api(
                "/datos?" +
                    new URLSearchParams({
                        page,
                        estado: $("chatFiltro").value,
                    }),
            );
            error(d.aviso);
            $("chatLista").replaceChildren(
                ...d.conversaciones.data.map((c) => {
                    const b = node(
                        "button",
                        c.nombre + " · " + c.estado,
                        "list-group-item list-group-item-action text-break",
                    );
                    b.type = "button";
                    b.onclick = () => {
                        selected = c; root.classList.add("chat-selected");
                        offset = 0;
                        $("chatPanel").classList.remove("d-none");
                        $("chatNombre").textContent = c.nombre;
                        $("chatResponsable").textContent =
                            "Responsable: " +
                            (c.responsable?.name || "Sin asignar");
                        reflejarResponsable();
                        history();
                    };
                    return b;
                }),
            );
            if (abrirDesdeNotificacion) {
                const index=d.conversaciones.data.findIndex(c=>String(c.id)===abrirDesdeNotificacion);
                if(index>=0){$("chatLista").children[index]?.click();abrirDesdeNotificacion=null;}
                else if(page<d.conversaciones.last_page){page++; loading=false;return load();}
                else abrirDesdeNotificacion=null;
            }
            $("chatAnterior").disabled = page <= 1;
            $("chatSiguiente").disabled = page >= d.conversaciones.last_page;
            if ($("chatUsuario")) {
                if (selected) selected = d.conversaciones.data.find(c => c.id === selected.id) || selected;
                $("chatUsuario").replaceChildren(
                    new Option("Sin asignar", ""),
                    ...d.usuarios.map((u) => new Option(u.nombre, u.id)),
                );
                reflejarResponsable();
            }
            if (selected) {
                selected = d.conversaciones.data.find(c=>c.id===selected.id) || selected;
                reflejarResponsable();
            }
            if (
                selected &&
                !d.conversaciones.data.some((c) => c.id === selected.id)
            ) {
                selected = null;
                $("chatPanel").classList.add("d-none");
                $("chatMensajes").replaceChildren();
                $("chatEventos").replaceChildren();
            }
        } catch (e) {
            error(e.message);
        } finally {
            loading = false;
        }
    }
    async function action(path, data) {
        if (!selected || busy) return false;
        busy = true;
        const id = selected.id;
        let failure = "";
        root.querySelectorAll("button").forEach((b) => (b.disabled = true));
        try {
            await api("/" + id + "/" + path, data);
            return true;
        } catch (e) {
            failure = e.message;
            return false;
        } finally {
            busy = false;
            root.querySelectorAll("button").forEach(
                (b) => (b.disabled = false),
            );
            await load();
            await history();
            reflejarResponsable();
            if (failure) error(failure);
        }
    }
    $("chatVolver")?.addEventListener("click",()=>root.classList.remove("chat-selected"));
    $("chatRespuesta")?.addEventListener("submit", async (e) => {
        e.preventDefault();
        if (
            await action("responder", {
                texto: $("chatTexto").value,
                solicitud: uuid(),
            })
        )
            $("chatTexto").value = "";
    });
    $("chatDocumento")?.addEventListener("submit", async (e) => {
        e.preventDefault();
        const data = new FormData();
        data.append("documento", $("chatPdf").files[0]);
        data.append("solicitud", uuid());
        if (await action("documento", data)) $("chatPdf").value = "";
    });
    $("chatNota")?.addEventListener("submit", async (e) => {
        e.preventDefault();
        if (await action("nota", { texto: $("chatNotaTexto").value }))
            $("chatNotaTexto").value = "";
    });
    $("chatAsignacion")?.addEventListener("submit", (e) => {
        e.preventDefault();
        action("asignar", { usuario: $("chatUsuario").value || null });
    });
    $("chatCerrar")?.addEventListener("click", () =>
        action("atender", { estado: "atendida" }),
    );
    $("chatReabrir")?.addEventListener("click", () =>
        action("atender", { estado: "en_atencion" }),
    );
    $("chatFiltro").onchange = () => {
        page = 1;
        selected = null;
        $("chatPanel").classList.add("d-none");
        load();
    };
    $("chatAnterior").onclick = () => {
        page--;
        load();
    };
    $("chatSiguiente").onclick = () => {
        page++;
        load();
    };
    $("chatAnteriores").onclick = () => {
        offset += 100;
        history();
    };
    $("chatRecientes").onclick = () => {
        offset = Math.max(0, offset - 100);
        history();
    };
    setInterval(() => {
        if (!document.hidden && !busy) {
            load();
            history();
        }
    }, 15000);
    load();
})();
