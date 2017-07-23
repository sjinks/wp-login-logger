PHP_FILES := $(wildcard ../*.php) $(wildcard ../views/*.php) $(wildcard ../inc/*.php)
DOMAIN    := login-logger
PO_FILES  := $(wildcard $(DOMAIN)-*.po)
MO_FILES  := $(patsubst %.po,%.mo,$(PO_FILES))

POT       := $(DOMAIN).pot

XGETTEXT  := xgettext
MSGFMT    := msgfmt
MSGMERGE  := msgmerge

XGETTEXT_OPTIONS := --default-domain=$(DOMAIN) --language=php \
	--keyword=__ \
	--keyword=_e \
	--keyword=_x \
	--keyword=_ex \
	--keyword=_n \
	--keyword=_nx \
	--keyword=_n_noop \
	--keyword=_nx_noop \
	--keyword=translate_nooped_plural \
	--keyword=esc_html__ \
	--keyword=esc_html_e \
	--keyword=esc_html_x \
	--keyword=esc_attr__ \
	--keyword=esc_attr_e \
	--keyword=esc_attr_x \
	--from-code=UTF-8 --omit-header

.PHONY: all clean

all: $(POT) $(MO_FILES)

$(MO_FILES): %.mo: %.po
	$(MSGFMT) -o$@ $<

$(PO_FILES): %: $(POT)
	$(MSGMERGE) -U "$@" $(POT)
	touch "$@"

$(POT): $(PHP_FILES)
	$(XGETTEXT) $(XGETTEXT_OPTIONS) $(PHP_FILES) --output="$@"

clean:
	-rm -f $(POT) $(MO_FILES)
